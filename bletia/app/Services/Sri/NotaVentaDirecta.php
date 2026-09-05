<?php

namespace App\Services\Sri;

use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;

/**
 * Genera/envía la Nota de Venta para una VENTA DIRECTA de stock (sin pedido).
 * Reutiliza la MISMA vista sri.nota-venta que ya usan los pedidos, sin tocarla.
 * Paralelo a NotaVenta::generar()/enviar(), que siguen intactos para pedidos.
 */
class NotaVentaDirecta
{
    public static function generar(Venta $venta, array $itemsSri): string
    {
        $cliente = $venta->cliente;
        $emisor = [
            'razon' => \App\Models\Ajuste::get('emisor_razon', 'EMISOR'),
            'comercial' => \App\Models\Ajuste::get('emisor_nombre_comercial', ''),
            'ruc' => \App\Models\Ajuste::get('emisor_ruc', ''),
            'dir_matriz' => \App\Models\Ajuste::get('emisor_dir_matriz', ''),
        ];

        $items = []; $subtotal = 0; $totalIva = 0;
        foreach ($itemsSri as $d) {
            $cant = (float) $d['cantidad'];
            $pu = (float) $d['precio_unitario']; // ya viene sin IVA
            $rate = (float) $d['iva_rate'];
            $base = round($pu * $cant, 2);
            $ivaLinea = round($base * $rate / 100, 2);
            $subtotal += $base; $totalIva += $ivaLinea;
            $items[] = [
                'codigo' => $d['codigo'], 'descripcion' => $d['descripcion'],
                'nombre' => $d['descripcion'], 'detalles' => '',
                'cantidad' => $cant, 'precio_unitario' => $pu, 'descuento' => 0, 'total' => $base,
            ];
        }
        $total = round($subtotal + $totalIva, 2);

        $html = view('sri.nota-venta', [
            'venta' => $venta, 'cliente' => $cliente, 'emisor' => $emisor,
            'items' => $items, 'subtotal' => round($subtotal, 2), 'totalIva' => round($totalIva, 2), 'total' => $total,
            'pagos' => FormasPago::desglosePorVenta($venta),
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
