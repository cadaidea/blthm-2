<?php

namespace App\Services\Sri;

use App\Models\SriComprobante;
use Barryvdh\DomPDF\Facade\Pdf;

/** Genera el RIDE (PDF) de un comprobante autorizado. */
class Ride
{
    public static function generar(SriComprobante $c): string
    {
        $detalles = is_array($c->detalles) ? $c->detalles : (json_decode($c->detalles ?? '[]', true) ?: []);
        $emisor = [
            'razon' => \App\Models\Ajuste::get('emisor_razon', 'EMISOR'),
            'comercial' => \App\Models\Ajuste::get('emisor_nombre_comercial', ''),
            'ruc' => \App\Models\Ajuste::get('emisor_ruc', ''),
            'dir_matriz' => \App\Models\Ajuste::get('emisor_dir_matriz', ''),
            'dir_estab' => \App\Models\Ajuste::get('emisor_dir_estab', ''),
            'obligado' => \App\Models\Ajuste::get('emisor_obligado_contabilidad', 'NO'),
            'contribuyente_especial' => \App\Models\Ajuste::get('emisor_contribuyente_especial', ''),
            'regimen_micro' => \App\Models\Ajuste::get('emisor_regimen_micro', ''),
            'ambiente' => $c->ambiente === '2' ? 'PRODUCCIÓN' : 'PRUEBAS',
        ];

        $items = [];
        $subtotal = 0; $totalIva = 0; $totalDesc = 0;
        foreach ($detalles as $d) {
            $cant = (float) ($d['cantidad'] ?? 1);
            $pu = (float) ($d['precio_unitario'] ?? 0);
            $desc = (float) ($d['descuento'] ?? 0);
            $base = ($cant * $pu) - $desc;
            $rate = (float) ($d['iva_rate'] ?? 15);
            $iva = round($base * $rate / 100, 2);
            $subtotal += $base; $totalIva += $iva; $totalDesc += $desc;
            $items[] = [
                'codigo' => $d['codigo'] ?? '', 'descripcion' => $d['descripcion'] ?? '',
                'cantidad' => $cant, 'precio_unitario' => $pu, 'descuento' => $desc, 'total' => $base,
            ];
        }
        $total = $subtotal + $totalIva;

        $html = view('sri.ride', array_merge(
            compact('c', 'emisor', 'items', 'subtotal', 'totalIva', 'totalDesc', 'total'),
            [
                'pagos' => self::pagosDeComprobante($c),
                'origen' => self::origenDeComprobante($c),
                'infoAdicional' => self::infoDeComprobante($c),
            ]
        ))->render();
        $pdf = Pdf::loadHTML($html)->setPaper('a4');
        $dir = storage_path('app/sri');
        if (! is_dir($dir)) @mkdir($dir, 0775, true);
        $path = $dir . '/RIDE_' . $c->clave_acceso . '.pdf';
        $pdf->save($path);
        $c->update(['pdf_path' => $path]);
        return $path;
    }

    /** Formas de pago: desde la venta enlazada (o el pedido) del comprobante. */
    protected static function pagosDeComprobante($c): array
    {
        $venta = \App\Models\Venta::where('sri_comprobante_id', $c->id)->first();
        $pedidoId = $venta->pedido_id ?? $c->pedido_id ?? null;
        if ($pedidoId) {
            $pedido = \App\Models\Pedido::find($pedidoId);
            if ($pedido) return \App\Services\Sri\FormasPago::desglosePedido($pedido);
        }
        // venta directa de stock (sin pedido): desglose por venta_id
        if ($venta) return \App\Services\Sri\FormasPago::desglosePorVenta($venta);
        return [];
    }

    /** Código de origen (web/woo) desde la venta o el pedido. */
    protected static function origenDeComprobante($c): ?string
    {
        $venta = \App\Models\Venta::where('sri_comprobante_id', $c->id)->first();
        if ($venta && $venta->codigo_origen) return $venta->codigo_origen;
        $pedidoId = $venta->pedido_id ?? $c->pedido_id ?? null;
        if ($pedidoId && ($p = \App\Models\Pedido::find($pedidoId))) return $p->codigo_origen ?: null;
        return null;
    }

    /** Información adicional desde la venta. */
    protected static function infoDeComprobante($c): ?string
    {
        $venta = \App\Models\Venta::where('sri_comprobante_id', $c->id)->first();
        return $venta->info_adicional ?? null;
    }
}
