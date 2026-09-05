<?php

namespace App\Filament\Pages;

use App\Filament\Resources\DespachoResource;
use App\Filament\Resources\PedidoEspecialResource;
use App\Filament\Resources\ProduccionResource;
use App\Filament\Resources\VentaResource;
use App\Models\PedidoEspecial;
use App\Services\EstadoPedidoErp;
use App\Support\Acl;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class TableroBletia extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Tablero';
    protected static ?string $title = 'Tablero';
    protected static ?int $navigationSort = -10;
    protected string $view = 'filament.pages.tablero-bletia';

    public static function shouldRegisterNavigation(): bool { return true; }

    /* ---- accesos rápidos según rol ---- */
    public function accesos(): array
    {
        $rol = Acl::rol();
        $ventas = in_array($rol, ['admin', 'operaciones', 'contabilidad', 'vendedor'], true);
        $btn = [];
        if ($ventas) {
            $btn[] = ['t' => 'Vender stock', 'i' => 'heroicon-o-cube', 'c' => '#16a34a', 'u' => VenderStockDirecto::getUrl()];
            $btn[] = ['t' => 'Vender bajo pedido', 'i' => 'heroicon-o-wrench-screwdriver', 'c' => '#0499FC', 'u' => CrearPedidoBletia::getUrl()];
            $btn[] = ['t' => 'Tomar online', 'i' => 'heroicon-o-inbox-arrow-down', 'c' => '#7c3aed', 'u' => $this->urlCls(\App\Filament\Pages\TomarOnlineBletia::class)];
        }
        return $btn;
    }

    public function listas(): array
    {
        $u = fn ($c) => $this->urlCls($c);
        $rol = Acl::rol();
        $out = [];
        if (in_array($rol, ['admin', 'operaciones', 'contabilidad', 'vendedor'], true)) $out[] = ['t' => 'Pedidos', 'i' => 'heroicon-o-clipboard-document-list', 'u' => $u(PedidoEspecialResource::class)];
        if (in_array($rol, ['admin', 'operaciones', 'contabilidad'], true)) $out[] = ['t' => 'Ventas', 'i' => 'heroicon-o-banknotes', 'u' => $u(VentaResource::class)];
        if (in_array($rol, ['admin', 'operaciones', 'produccion'], true)) $out[] = ['t' => 'Fabricación', 'i' => 'heroicon-o-cog-6-tooth', 'u' => $u(ProduccionResource::class)];
        if (in_array($rol, ['admin', 'operaciones', 'bodega'], true)) $out[] = ['t' => 'Despacho', 'i' => 'heroicon-o-truck', 'u' => $u(DespachoResource::class)];
        return $out;
    }

    protected function urlCls(string $cls): string { return class_exists($cls) ? $cls::getUrl() : '#'; }

    /* ---- Kanban por etapas según rol ---- */
    public function kanban(): array
    {
        $rol = Acl::rol();
        $q = PedidoEspecial::query()->whereNotIn('estado_erp', ['entregado', 'anulado', 'cancelado']);
        if (in_array($rol, ['admin', 'operaciones'], true)) {
        } elseif ($rol === 'vendedor') {
            $q->where('vendedor_id', auth()->id());
        } elseif ($rol === 'produccion') {
            $q->whereIn('estado_erp', ['enviado_proveedor', 'en_fabricacion', 'en_produccion', 'listo_proveedor']);
        } elseif ($rol === 'bodega') {
            $q->whereIn('estado_erp', ['en_bodega', 'listo_despacho', 'despachado']);
        } elseif ($rol === 'contabilidad') {
        } else {
            $q->whereRaw('1=0');
        }
        $pedidos = $q->orderByDesc('id')->limit(120)->get();

        // columnas (grupos de estados)
        $cols = [
            'Por aprobar'    => ['pendiente', 'por_aprobar'],
            'Aprobado'       => ['aprobado', 'confirmado'],
            'En fabricación' => ['enviado_proveedor', 'en_fabricacion', 'en_produccion', 'listo_proveedor'],
            'En bodega'      => ['en_bodega'],
            'Listo despacho' => ['listo_despacho', 'despachado'],
        ];
        $colColor = [
            'Por aprobar' => '#f59e0b', 'Aprobado' => '#3b82f6', 'En fabricación' => '#6366f1',
            'En bodega' => '#14b8a6', 'Listo despacho' => '#10b981',
        ];
        $hoy = \Illuminate\Support\Carbon::today();
        $out = [];
        foreach ($cols as $titulo => $estados) {
            $cards = [];
            foreach ($pedidos as $p) {
                if (! in_array($p->estado_erp, $estados, true)) continue;
                $fin = $p->fecha_comprometida ? \Illuminate\Support\Carbon::parse($p->fecha_comprometida) : ($p->fecha_solicitada ? \Illuminate\Support\Carbon::parse($p->fecha_solicitada) : null);
                $cards[] = [
                    'folio' => $p->folio ?: ('#' . $p->id),
                    'cliente' => optional($p->cliente)->nombre ?? '—',
                    'fin' => $fin ? $fin->format('d/m') : '—',
                    'atrasado' => $fin ? $fin->lt($hoy) : false,
                    'url' => PedidoEspecialResource::getUrl('view', ['record' => $p->id]),
                ];
            }
            $out[] = ['titulo' => $titulo, 'color' => $colColor[$titulo], 'n' => count($cards), 'cards' => $cards];
        }
        return $out;
    }

    /* ---- datos del Gantt según rol ---- */
    public function gantt(): array
    {
        $rol = Acl::rol();
        $q = PedidoEspecial::query()->whereNotIn('estado_erp', ['entregado', 'anulado', 'cancelado']);

        // alcance por rol
        if (in_array($rol, ['admin', 'operaciones'], true)) {
            // todo
        } elseif ($rol === 'vendedor') {
            $q->where('vendedor_id', auth()->id());
        } elseif ($rol === 'produccion') {
            $q->whereIn('estado_erp', ['enviado_proveedor', 'en_fabricacion', 'en_produccion', 'listo_proveedor']);
        } elseif ($rol === 'bodega') {
            $q->whereIn('estado_erp', ['en_bodega', 'listo_despacho', 'despachado']);
        } elseif ($rol === 'contabilidad') {
            // pedidos con saldo pendiente
        } else {
            $q->whereRaw('1=0');
        }

        $pedidos = $q->orderBy('fecha_comprometida')->limit(60)->get();

        // ventana temporal global
        $hoy = Carbon::today();
        $min = $hoy->copy()->subDays(3);
        $max = $hoy->copy()->addDays(21);
        foreach ($pedidos as $p) {
            $ini = $p->created_at ? Carbon::parse($p->created_at)->startOfDay() : $hoy;
            $fin = $p->fecha_comprometida ? Carbon::parse($p->fecha_comprometida) : ($p->fecha_solicitada ? Carbon::parse($p->fecha_solicitada) : $hoy->copy()->addDays(7));
            if ($ini->lt($min)) $min = $ini->copy();
            if ($fin->gt($max)) $max = $fin->copy();
        }
        $totalDias = max(1, $min->diffInDays($max));

        $colores = [
            'pendiente' => '#9ca3af', 'por_aprobar' => '#f59e0b', 'aprobado' => '#3b82f6',
            'enviado_proveedor' => '#6366f1', 'en_fabricacion' => '#6366f1', 'en_produccion' => '#8b5cf6',
            'listo_proveedor' => '#0ea5e9', 'en_bodega' => '#14b8a6', 'listo_despacho' => '#10b981', 'despachado' => '#22c55e',
        ];

        $filas = [];
        foreach ($pedidos as $p) {
            $ini = $p->created_at ? Carbon::parse($p->created_at)->startOfDay() : $hoy;
            $fin = $p->fecha_comprometida ? Carbon::parse($p->fecha_comprometida) : ($p->fecha_solicitada ? Carbon::parse($p->fecha_solicitada) : $hoy->copy()->addDays(7));
            if ($fin->lt($ini)) $fin = $ini->copy()->addDay();
            $offset = $min->diffInDays($ini) / $totalDias * 100;
            $ancho = max(2, $ini->diffInDays($fin) / $totalDias * 100);
$atrasado = $fin->lt($hoy);
            $diasRest = $hoy->diffInDays($fin, false);
            $filas[] = [
                'id' => $p->id,
                'folio' => $p->folio ?: ('#' . $p->id),
                'cliente' => optional($p->cliente)->nombre ?? '—',
                'estado' => EstadoPedidoErp::ESTADOS[$p->estado_erp] ?? $p->estado_erp,
                'color' => $atrasado ? '#ef4444' : ($colores[$p->estado_erp] ?? '#64748b'),
                'offset' => round($offset, 2),
                'ancho' => round($ancho, 2),
                'fin' => $fin->format('d/m'),
                'atrasado' => $atrasado,
                'dias' => $diasRest,
                'url' => PedidoEspecialResource::getUrl('view', ['record' => $p->id]),
            ];
        }

        // marcas de tiempo (líneas semanales) + hoy
        $hoyPct = round($min->diffInDays($hoy) / $totalDias * 100, 2);
        $totalPed = count($filas);
        $atrasadosN = collect($filas)->where('atrasado', true)->count();
        $enTiempoN = $totalPed - $atrasadosN;
        return ['filas' => $filas, 'min' => $min->format('d/m'), 'max' => $max->format('d/m'), 'hoy' => $hoyPct, 'totalPed' => $totalPed, 'atrasadosN' => $atrasadosN, 'enTiempoN' => $enTiempoN];
    }

    /* ---- Panel financiero (solo admin/contabilidad) ---- */
    public function panelFinanciero(): ?array
    {
        if (! (\App\Support\Acl::esAdmin() || \App\Support\Acl::esContabilidad())) return null;

        $db = \Illuminate\Support\Facades\DB::class;

        // cuentas por cobrar
        $pedidosConSaldo = \Illuminate\Support\Facades\DB::table('pedidos')
            ->whereNotIn('estado_erp', ['anulado', 'cancelado'])
            ->where('total', '>', 0)->get(['id', 'total']);
        $cuentasPorCobrar = 0.0; $pedidosConDeuda = 0;
        foreach ($pedidosConSaldo as $p) {
            $pagado = (float) \Illuminate\Support\Facades\DB::table('recibos')->where('pedido_id', $p->id)->where('validado', 1)->sum('monto');
            $saldo = (float) $p->total - $pagado;
            if ($saldo > 0.5) { $cuentasPorCobrar += $saldo; $pedidosConDeuda++; }
        }

        // utilidad estimada del mes
        $itemsMes = \Illuminate\Support\Facades\DB::table('pedido_items')
            ->join('pedidos', 'pedidos.id', '=', 'pedido_items.pedido_id')
            ->whereNotIn('pedidos.estado_erp', ['anulado', 'cancelado'])
            ->whereMonth('pedidos.created_at', now()->month)->whereYear('pedidos.created_at', now()->year)
            ->select('pedido_items.*', 'pedidos.destino_fab as destino_fab')
            ->get();
        $ventaTotalMes = 0.0; $costoTotalMes = 0.0; $itemsConCosto = 0; $itemsSinCosto = 0;
        foreach ($itemsMes as $it) {
            $ventaTotalMes += (float) $it->subtotal;
            $prod = $it->producto_id ? \Illuminate\Support\Facades\DB::table('productos')->where('id', $it->producto_id)->first() : null;
            $costo = null;
            if ($prod) $costo = $it->destino_fab === 'proveedor' ? $prod->costo_proveedor : $prod->costo_produccion;
            if ($costo !== null) { $costoTotalMes += (float) $costo * (float) ($it->cantidad ?: 1); $itemsConCosto++; }
            else { $itemsSinCosto++; }
        }
        $utilidadMes = $ventaTotalMes - $costoTotalMes;
        $utilidadCompleta = $itemsSinCosto === 0 && $itemsConCosto > 0;

        // top 5 productos del mes
        $topProductos = \Illuminate\Support\Facades\DB::table('pedido_items')
            ->join('pedidos', 'pedidos.id', '=', 'pedido_items.pedido_id')
            ->whereNotIn('pedidos.estado_erp', ['anulado', 'cancelado'])
            ->whereMonth('pedidos.created_at', now()->month)->whereYear('pedidos.created_at', now()->year)
            ->select('pedido_items.nombre', \Illuminate\Support\Facades\DB::raw('SUM(pedido_items.cantidad) as total_cant'), \Illuminate\Support\Facades\DB::raw('SUM(pedido_items.subtotal) as total_venta'))
            ->groupBy('pedido_items.nombre')->orderByDesc('total_cant')->limit(5)->get();

        // cheques próximos (7 días)
        $chequesProximos = \Illuminate\Support\Facades\Schema::hasTable('recibos') ? \Illuminate\Support\Facades\DB::table('recibos')
            ->where('metodo', 'cheque')->where('cheque_cobrado', false)
            ->whereNotIn('cheque_estado', ['anulado', 'rechazado', 'cobrado'])
            ->whereNotNull('cheque_fecha_cobro')
            ->whereDate('cheque_fecha_cobro', '<=', now()->addDays(7)->toDateString())
            ->orderBy('cheque_fecha_cobro')->limit(5)
            ->get(['cheque_numero', 'cheque_banco', 'cheque_fecha_cobro', 'monto']) : collect();

        // ventas mes actual vs anterior
        $ventasMesActual = (float) \Illuminate\Support\Facades\DB::table('pedidos')
            ->whereNotIn('estado_erp', ['anulado', 'cancelado'])
            ->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('total');
        $ventasMesAnterior = (float) \Illuminate\Support\Facades\DB::table('pedidos')
            ->whereNotIn('estado_erp', ['anulado', 'cancelado'])
            ->whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year)->sum('total');
        $variacion = $ventasMesAnterior > 0 ? round((($ventasMesActual - $ventasMesAnterior) / $ventasMesAnterior) * 100, 1) : null;

        return [
            'cuentas_por_cobrar' => $cuentasPorCobrar,
            'pedidos_con_deuda'  => $pedidosConDeuda,
            'utilidad_mes'       => $utilidadMes,
            'utilidad_completa'  => $utilidadCompleta,
            'venta_total_mes'    => $ventaTotalMes,
            'top_productos'      => $topProductos,
            'cheques_proximos'   => $chequesProximos,
            'ventas_mes_anterior'=> $ventasMesAnterior,
            'variacion'          => $variacion,
        ];
    }
}
