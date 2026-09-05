<?php

namespace App\Services;

use App\Models\Pedido;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;

class Facturacion
{
    /**
     * Registra un pedido como venta confirmada (para contabilidad/declaraciones).
     * Idempotente: un pedido => una sola venta. NO toca estado_erp ni frena el flujo.
     */
    public static function registrar(Pedido $pedido, string $nroFactura, ?string $fecha = null): Venta
    {
        $existe = Venta::where('pedido_id', $pedido->id)->first();
        if ($existe) {
            return $existe;
        }

        $total = round((float) DB::table('pedido_items')->where('pedido_id', $pedido->id)->sum('subtotal'), 2);
        $fechaVenta = $fecha ?: now()->toDateString();
        $base  = round($total / \App\Support\Impuestos::divisorIva($fechaVenta), 2); // PVP incluye IVA vigente
        $iva   = round($total - $base, 2);

        // Opción B: el folio del registro de venta es el número del comprobante (SRI o VEN).
        $folio = $nroFactura;

        $venta = Venta::create([
            'pedido_id'     => $pedido->id,
            'nro_factura'   => $nroFactura,
            'folio'         => $folio,
            'fecha'         => $fecha ?: now()->toDateString(),
            'cliente_id'    => $pedido->cliente_id,
            'local_id'      => $pedido->local_id,
            'vendedor_id'   => $pedido->vendedor_id,
            'forma_venta'   => $pedido->forma_venta,
            'subtotal'      => $base,
            'iva'           => $iva,
            'total'         => $total,
            'estado'        => 'emitida',
            'facturado_por' => auth()->id(),
            'facturado_at'  => now(),
        ]);

        DB::table('pedidos')->where('id', $pedido->id)->update([
            'nro_factura'   => $nroFactura,
            'facturado_at'  => now(),
            'facturado_por' => auth()->id(),
        ]);

        try {
            if (class_exists(\App\Services\Bitacora::class)) {
                \App\Services\Bitacora::registrar('facturado', 'Venta', $venta->id, "Factura {$nroFactura} · pedido #{$pedido->id}");
            }
        } catch (\Throwable $e) {}

        return $venta;
    }
}
