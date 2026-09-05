<?php

namespace App\Services\Sri;

use App\Models\Venta;
use App\Mail\ComprobanteSriMail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;

/** Genera y envía la Nota de Venta (documento interno, no SRI). */
class NotaVenta
{
    public static function generar(Venta $venta): string
    {
        $pedido = $venta->pedido;
        $cliente = $venta->cliente;
        $detalles = [];
        foreach (\Illuminate\Support\Facades\DB::table('pedido_items')->where('pedido_id', $venta->pedido_id)->get() as $it) {
            $detalles[] = [
                'codigo' => (string) ($it->producto_id ?: $it->id),
                'descripcion' => DetalleItem::descripcion($it),
                'nombre' => $it->nombre ?: 'Producto',
                'detalles' => DetalleItem::detalles($it),
                'cantidad' => (float) $it->cantidad,
                'precio_unitario' => (float) $it->precio,
                'descuento' => 0,
                'iva_rate' => (float) ($it->iva_rate ?? 15),
            ];
        }

        $emisor = [
            'razon' => \App\Models\Ajuste::get('emisor_razon', 'EMISOR'),
            'comercial' => \App\Models\Ajuste::get('emisor_nombre_comercial', ''),
            'ruc' => \App\Models\Ajuste::get('emisor_ruc', ''),
            'dir_matriz' => \App\Models\Ajuste::get('emisor_dir_matriz', ''),
        ];

        $items = []; $subtotal = 0; $totalIva = 0;
        foreach ($detalles as $d) {
            $cant = (float) $d['cantidad'];
            $pvp = (float) $d['precio_unitario'];
            $rate = (float) $d['iva_rate'];
            $lineTotal = $cant * $pvp;
            $base = $rate > 0 ? round($lineTotal / (1 + $rate / 100), 2) : $lineTotal;
            $subtotal += $base; $totalIva += ($lineTotal - $base);
            $items[] = ['codigo' => $d['codigo'], 'descripcion' => $d['descripcion'], 'nombre' => $d['nombre'] ?? $d['descripcion'], 'detalles' => $d['detalles'] ?? '', 'cantidad' => $cant,
                'precio_unitario' => $rate > 0 ? round($pvp / (1 + $rate / 100), 4) : $pvp, 'descuento' => 0, 'total' => $base];
        }
        $total = round($subtotal + $totalIva, 2);

        $html = view('sri.nota-venta', [
            'venta' => $venta, 'cliente' => $cliente, 'emisor' => $emisor,
            'items' => $items, 'subtotal' => round($subtotal, 2), 'totalIva' => round($totalIva, 2), 'total' => $total,
            'pagos' => FormasPago::desglosePedido($pedido),
        ])->render();

        $pdf = Pdf::loadHTML($html)->setPaper('a4');
        $dir = storage_path('app/sri');
        if (! is_dir($dir)) @mkdir($dir, 0775, true);
        $path = $dir . '/NV_' . $venta->numero_comprobante . '.pdf';
        $pdf->save($path);
        return $path;
    }

    public static function enviar(Venta $venta, string $pdfPath): void
    {
        $cliente = $venta->cliente;
        if (! $cliente || ! $cliente->email) return;
        Mail::to($cliente->email)->send(new \App\Mail\NotaVentaMail($venta, $pdfPath));
    }
}
