<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\MovimientoStock;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
use App\Models\Recibo;
use App\Models\WooPedido;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TomarOnline
{
    /** Stock total disponible de un producto. */
    public static function stockDe(int $productoId): int
    {
        return (int) DB::table('stock')->where('producto_id', $productoId)->sum('cantidad');
    }

    /** Pedidos de la tienda propia listos para tomar (pagados, sin entrar al ERP). */
    public static function pendientesPropios()
    {
        return Pedido::query()
            ->where('estado', 'pagado')
            ->where(function ($q) { $q->whereNull('estado_erp')->orWhere('estado_erp', ''); })
            ->whereNull('vendedor_id')
            ->orderByDesc('id')->limit(100)->get();
    }

    /** Pedidos Woo importados aún no convertidos al ERP. */
    public static function pendientesWoo()
    {
        $convertidos = DB::table('pedidos')->whereNotNull('woo_id')->pluck('woo_id')->all();
        return WooPedido::query()
            ->when($convertidos, fn ($q) => $q->whereNotIn('woo_id', $convertidos))
            ->orderByDesc('id')->limit(100)->get();
    }

    /**
     * Toma un pedido de la TIENDA PROPIA: ya existe en 'pedidos'.
     * Decide stock vs fabricación por disponibilidad. Registra recibo PayPhone pagado.
     */
    public static function tomarPropio(Pedido $pedido, int $userId): array
    {
        if ($pedido->vendedor_id) return ['ok' => false, 'msg' => 'Este pedido ya fue tomado.'];

        $items = PedidoItem::where('pedido_id', $pedido->id)->get();
        $todoEnStock = true;
        foreach ($items as $it) {
            if (! $it->producto_id) { $todoEnStock = false; continue; }
            if (self::stockDe((int) $it->producto_id) < (int) $it->cantidad) { $todoEnStock = false; }
        }

        DB::transaction(function () use ($pedido, $userId, $items, $todoEnStock) {
            DB::table('pedidos')->where('id', $pedido->id)->update([
                'vendedor_id' => $userId,
                'tipo_erp'    => 'online',
                'forma_venta' => $todoEnStock ? 'stock' : 'online',
                'vendido_por' => $userId,
                'vendido_at'  => now(),
                'updated_at'  => now(),
            ]);
            $p = Pedido::find($pedido->id);

            // recibo del pago online (PayPhone / tarjeta) ya aprobado
            if ((float) RecibosErp::pagado($p) <= 0) {
                Recibo::create([
                    'pedido_id' => $p->id, 'cliente_id' => $p->cliente_id,
                    'tipo' => 'pago', 'monto' => (float) $p->total, 'metodo' => 'tarjeta',
                    'fecha' => now()->toDateString(), 'validado' => true, 'validado_por' => $userId, 'validado_at' => now(),
                    'nota' => 'Pago online PayPhone' . ($p->pp_transaction_id ? ' · tx ' . $p->pp_transaction_id : ''),
                    'recibido_por' => 'PayPhone',
                ]);
            }

            Traza::registrar($p, 'vendido', 'Tomado de venta online');
            if ($todoEnStock) {
                foreach ($items as $it) {
                    if (! $it->producto_id) continue;
                    MovimientoStock::create([
                        'producto_id' => $it->producto_id,
                        'local_id' => \App\Models\User::find($userId)->local_id ?? \App\Models\Local::value('id'),
                        'tipo' => 'salida', 'cantidad' => max(1, (int) $it->cantidad),
                        'referencia' => $p->folio ?: $p->codigo, 'nota' => 'Venta online (stock)',
                    ]);
                }
                EstadoPedidoErp::avanzar($p, 'en_bodega', false);
            } else {
                EstadoPedidoErp::avanzar($p, 'por_aprobar', false);
                FlujoErp::alarmaPorAprobar($p->fresh());
            }
        });

        self::avisar($pedido->fresh(), $todoEnStock);
        return ['ok' => true, 'stock' => $todoEnStock, 'msg' => $todoEnStock ? 'Tomado como venta de stock (a despacho).' : 'Tomado como pedido a fabricar (a aprobación).'];
    }

    /** Toma un pedido WOO: crea el Pedido del ERP mapeando ítems por SKU. */
    public static function tomarWoo(WooPedido $woo, int $userId): array
    {
        if (DB::table('pedidos')->where('woo_id', $woo->woo_id)->exists()) return ['ok' => false, 'msg' => 'Este pedido Woo ya fue tomado.'];

        // cliente por email
        $cliente = null;
        if ($woo->cliente_email) {
            $cliente = Cliente::firstOrCreate(['email' => $woo->cliente_email], ['nombre' => $woo->cliente_nombre ?: 'Cliente web']);
        } elseif ($woo->cliente_nombre) {
            $cliente = Cliente::firstOrCreate(['nombre' => $woo->cliente_nombre]);
        }

        $lineas = $woo->items()->get();
        $todoEnStock = true; $mapeados = [];
        foreach ($lineas as $l) {
            $prod = $l->sku ? Producto::where('sku', $l->sku)->first() : Producto::where('nombre', $l->producto_nombre)->first();
            $cant = max(1, (int) $l->cantidad);
            if (! $prod || self::stockDe((int) $prod->id) < $cant) $todoEnStock = false;
            $mapeados[] = ['prod' => $prod, 'linea' => $l, 'cant' => $cant];
        }

        $pedidoId = DB::transaction(function () use ($woo, $userId, $cliente, $mapeados, $todoEnStock) {
            $total = (float) $woo->total;
            $neto = round($total / 1.15, 2);
            $pedido = new Pedido();
            $pedido->codigo = 'WOO-' . ($woo->numero ?: $woo->woo_id);
            $pedido->cliente_id = $cliente->id ?? null;
            $pedido->email = $woo->cliente_email;
            $pedido->estado = 'pagado';
            $pedido->estado_erp = $todoEnStock ? 'en_bodega' : 'por_aprobar';
            $pedido->tipo_erp = 'online';
            $pedido->forma_venta = $todoEnStock ? 'stock' : 'online';
            $pedido->vendedor_id = $userId;
            $pedido->vendido_por = $userId;
            $pedido->vendido_at = now();
            $pedido->woo_id = $woo->woo_id;
            $pedido->subtotal = $neto;
            $pedido->iva = round($total - $neto, 2);
            $pedido->total = $total;
            $pedido->save();

            foreach ($mapeados as $m) {
                $l = $m['linea']; $prod = $m['prod'];
                $pvp = (float) $l->precio;
                $netoU = round($pvp / 1.15, 2);
                PedidoItem::create([
                    'pedido_id' => $pedido->id,
                    'producto_id' => $prod->id ?? null,
                    'nombre' => $prod->nombre ?? $l->producto_nombre,
                    'variantes' => $l->variaciones ?: null,
                    'precio' => $netoU, 'iva_rate' => 15, 'cantidad' => $m['cant'],
                    'subtotal' => round($netoU * $m['cant'], 2),
                ]);
            }

            Recibo::create([
                'pedido_id' => $pedido->id, 'cliente_id' => $pedido->cliente_id,
                'tipo' => 'pago', 'monto' => $total, 'metodo' => 'tarjeta',
                'fecha' => now()->toDateString(), 'validado' => true, 'validado_por' => $userId, 'validado_at' => now(),
                'nota' => 'Pago online WooCommerce · #' . ($woo->numero ?: $woo->woo_id), 'recibido_por' => 'WooCommerce',
            ]);

            Traza::registrar($pedido, 'vendido', 'Tomado de WooCommerce');
            if ($todoEnStock) {
                foreach ($mapeados as $m) {
                    if (! $m['prod']) continue;
                    MovimientoStock::create([
                        'producto_id' => $m['prod']->id,
                        'local_id' => \App\Models\User::find($userId)->local_id ?? \App\Models\Local::value('id'),
                        'tipo' => 'salida', 'cantidad' => $m['cant'],
                        'referencia' => $pedido->folio ?: $pedido->codigo, 'nota' => 'Venta WooCommerce (stock)',
                    ]);
                }
            } else {
                FlujoErp::alarmaPorAprobar($pedido->fresh());
            }
            return $pedido->id;
        });

        self::avisar(Pedido::find($pedidoId), $todoEnStock);
        return ['ok' => true, 'stock' => $todoEnStock, 'msg' => $todoEnStock ? 'Woo tomado como venta de stock (a despacho).' : 'Woo tomado como pedido a fabricar (a aprobación).'];
    }

    protected static function avisar(Pedido $p, bool $stock): void
    {
        try {
            $folio = $p->folio ?: $p->codigo;
            $dest = DB::table('users')->whereIn('rol', ['operaciones', 'admin'])->where('activo', true)->get();
            foreach ($dest as $u) {
                if ($uu = \App\Models\User::find($u->id)) {
                    \Filament\Notifications\Notification::make()->success()
                        ->title('Venta online tomada · ' . $folio)
                        ->body($stock ? 'Stock descontado, va a despacho.' : 'Va a aprobación para fabricación.')
                        ->sendToDatabase($uu);
                }
            }
        } catch (\Throwable $e) { report($e); }
    }
}
