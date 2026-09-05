<?php
namespace App\Filament\Widgets;

use App\Support\Acl;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;

class InicioBletia extends Widget
{
    protected string $view = 'filament.widgets.inicio-bletia';
    protected int|string|array $columnSpan = 'full';
    protected static ?int $sort = -10;

    public function getViewData(): array
    {
        $rol = Acl::rol();
        $puedeAprobar = Acl::puedeAprobar();

        // ===== MÉTRICAS (tarjetas de color arriba) =====
        $base = DB::table('pedidos');
        $activos    = (clone $base)->whereNotIn('estado_erp', ['entregado', 'anulado', 'cancelado'])->count();
        $porAprobar = (clone $base)->where('estado_erp', 'por_aprobar')->count();
        $fabricacion= (clone $base)->whereIn('estado_erp', ['enviado_proveedor', 'en_fabricacion', 'en_produccion', 'listo_proveedor'])->count();
        $listos     = (clone $base)->whereIn('estado_erp', ['en_bodega', 'listo_despacho'])->count();
        $entregados = (clone $base)->where('estado_erp', 'entregado')->count();
        $ventasMes  = (clone $base)->whereNotIn('estado_erp', ['anulado', 'cancelado'])
            ->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('total');

        $metricas = [];
        $m = fn ($label, $valor, $icon, $color) => $metricas[] = compact('label', 'valor', 'icon', 'color');
        $m('Pedidos activos', (string) $activos, 'clipboard-document-list', '#0499FC');
        if ($puedeAprobar) $m('Por aprobar', (string) $porAprobar, 'bell-alert', '#e6862e');
        $m('En fabricación', (string) $fabricacion, 'wrench-screwdriver', '#7a5af8');
        $m('Listos / bodega', (string) $listos, 'archive-box', '#3d8b8b');
        $m('Entregados', (string) $entregados, 'check-circle', '#2e9e6b');
        if ($puedeAprobar || Acl::esContabilidad()) $m('Ventas del mes', '$' . number_format((float) $ventasMes, 2), 'banknotes', '#161921');

        // ===== ACCESOS RÁPIDOS =====
        $accesos = [];
        $add = fn ($label, $icon, $url, $color) => $accesos[] = compact('label', 'icon', 'url', 'color');
        if (Acl::ve(\App\Filament\Resources\PedidoEspecialResource::class)) $add('Pedidos', 'clipboard-document-list', '/dash/pedido-especials', '#0499FC');
        if (Acl::ve(\App\Filament\Resources\ReciboResource::class))         $add('Recibos', 'banknotes', '/dash/recibos', '#2e9e6b');
        if (Acl::ve(\App\Filament\Resources\ClienteResource::class))        $add('Clientes', 'users', '/dash/clientes', '#7a5af8');
        if (Acl::ve(\App\Filament\Resources\ProductoResource::class))       $add('Productos', 'cube', '/dash/productos', '#e6862e');
        if (Acl::ve(\App\Filament\Resources\DespachoResource::class))       $add('Despachos', 'truck', '/dash/despachos', '#d9534f');
        if (Acl::ve(\App\Filament\Resources\ProveedorResource::class))      $add('Proveedores', 'building-storefront', '/dash/proveedors', '#e6862e');
        if (class_exists(\App\Filament\Resources\ProduccionResource::class) && Acl::ve(\App\Filament\Resources\ProduccionResource::class)) $add('Producción', 'wrench-screwdriver', '/dash/produccions', '#7a5af8');
        if (class_exists(\App\Filament\Resources\MateriaPrimaResource::class) && Acl::ve(\App\Filament\Resources\MateriaPrimaResource::class)) $add('Materias primas', 'cube-transparent', '/dash/materia-primas', '#3d8b8b');
        if (Acl::ve(\App\Filament\Resources\MovimientoStockResource::class))$add('Inventario', 'archive-box', '/dash/movimiento-stocks', '#3d8b8b');

        // ===== PEDIDOS RECIENTES =====
        $q = DB::table('pedidos')->orderByDesc('id')->limit(8);
        if (Acl::esVendedor()) $q->where('vendedor_id', auth()->id());
        $pedidos = $q->get()->map(function ($p) {
            $dias = $p->fecha_comprometida ? now()->startOfDay()->diffInDays(Carbon::parse($p->fecha_comprometida), false)
                  : ($p->fecha_solicitada ? now()->startOfDay()->diffInDays(Carbon::parse($p->fecha_solicitada), false) : null);
            return [
                'id'     => $p->id,
                'folio'  => $p->folio ?: ('#' . $p->id),
                'estado' => $p->estado_erp ?: 'pendiente',
                'total'  => number_format((float) $p->total, 2),
                'dias'   => $dias,
                'forma'  => $p->forma_venta ?? '—',
            ];
        });

        // ===== GERENCIA FINANCIERA (solo admin/contabilidad) =====
        $gerencia = null;
        if (Acl::esAdmin() || Acl::esContabilidad()) {
            // cuentas por cobrar: suma de saldos pendientes de pedidos no anulados/cancelados
            $pedidosConSaldo = DB::table('pedidos')
                ->whereNotIn('estado_erp', ['anulado', 'cancelado'])
                ->where('total', '>', 0)
                ->get(['id', 'total']);
            $cuentasPorCobrar = 0.0; $pedidosConDeuda = 0;
            foreach ($pedidosConSaldo as $p) {
                $pagado = (float) DB::table('recibos')->where('pedido_id', $p->id)->where('validado', 1)->sum('monto');
                $saldo = (float) $p->total - $pagado;
                if ($saldo > 0.5) { $cuentasPorCobrar += $saldo; $pedidosConDeuda++; }
            }

            // utilidad estimada del mes (ventas con costo registrado)
            $itemsMes = DB::table('pedido_items')
                ->join('pedidos', 'pedidos.id', '=', 'pedido_items.pedido_id')
                ->whereNotIn('pedidos.estado_erp', ['anulado', 'cancelado'])
                ->whereMonth('pedidos.created_at', now()->month)->whereYear('pedidos.created_at', now()->year)
                ->select('pedido_items.*', 'pedidos.destino_fab as destino_fab')
                ->get();
            $ventaTotalMes = 0.0; $costoTotalMes = 0.0; $itemsConCosto = 0; $itemsSinCosto = 0;
            foreach ($itemsMes as $it) {
                $ventaTotalMes += (float) $it->subtotal;
                $prod = $it->producto_id ? DB::table('productos')->where('id', $it->producto_id)->first() : null;
                $costo = null;
                if ($prod) {
                    $costo = $it->destino_fab === 'proveedor' ? $prod->costo_proveedor : $prod->costo_produccion;
                }
                if ($costo !== null) {
                    $costoTotalMes += (float) $costo * (float) ($it->cantidad ?: 1);
                    $itemsConCosto++;
                } else {
                    $itemsSinCosto++;
                }
            }
            $utilidadMes = $ventaTotalMes - $costoTotalMes;
            $utilidadCompleta = $itemsSinCosto === 0 && $itemsConCosto > 0;

            // top 5 productos más vendidos (cantidad) del mes
            $topProductos = DB::table('pedido_items')
                ->join('pedidos', 'pedidos.id', '=', 'pedido_items.pedido_id')
                ->whereNotIn('pedidos.estado_erp', ['anulado', 'cancelado'])
                ->whereMonth('pedidos.created_at', now()->month)->whereYear('pedidos.created_at', now()->year)
                ->select('pedido_items.nombre', DB::raw('SUM(pedido_items.cantidad) as total_cant'), DB::raw('SUM(pedido_items.subtotal) as total_venta'))
                ->groupBy('pedido_items.nombre')
                ->orderByDesc('total_cant')
                ->limit(5)
                ->get();

            // cheques próximos a vencer (7 días) sin cobrar
            $chequesProximos = Schema::hasTable('recibos') ? DB::table('recibos')
                ->where('metodo', 'cheque')->where('cheque_cobrado', false)
                ->whereNotIn('cheque_estado', ['anulado', 'rechazado', 'cobrado'])
                ->whereNotNull('cheque_fecha_cobro')
                ->whereDate('cheque_fecha_cobro', '<=', now()->addDays(7)->toDateString())
                ->orderBy('cheque_fecha_cobro')
                ->limit(5)
                ->get(['cheque_numero', 'cheque_banco', 'cheque_fecha_cobro', 'monto']) : collect();

            // comparativo: ventas mes actual vs mes anterior
            $ventasMesAnterior = (float) DB::table('pedidos')
                ->whereNotIn('estado_erp', ['anulado', 'cancelado'])
                ->whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year)
                ->sum('total');
            $variacion = $ventasMesAnterior > 0 ? round((($ventasMes - $ventasMesAnterior) / $ventasMesAnterior) * 100, 1) : null;

            $gerencia = [
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

        return compact('metricas', 'accesos', 'pedidos', 'porAprobar', 'rol', 'gerencia');
    }
}
