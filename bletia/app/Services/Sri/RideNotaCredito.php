<?php

namespace App\Services\Sri;

use App\Models\SriComprobante;
use Barryvdh\DomPDF\Facade\Pdf;

/** Genera el RIDE PDF de una Nota de Crédito. */
class RideNotaCredito
{
    public static function generar(SriComprobante $c): string
    {
        $emisor = [
            'razon' => Emisor::razon(), 'comercial' => Emisor::nombreComercial(), 'ruc' => Emisor::ruc(),
            'dir_matriz' => Emisor::dirMatriz(), 'obligado' => Emisor::obligadoContabilidad(),
        ];
        $detalles = is_array($c->detalles) ? $c->detalles : (json_decode($c->detalles ?? '[]', true) ?: []);
        $items = []; $subtotal = 0; $totalIva = 0;
        foreach ($detalles as $d) {
            $cant = (float) ($d['cantidad'] ?? 1);
            $pu = (float) ($d['precio_unitario'] ?? 0);
            $rate = (float) ($d['iva_rate'] ?? 15);
            $base = round($cant * $pu, 2);
            $subtotal += $base; $totalIva += round($base * $rate / 100, 2);
            $items[] = ['codigo' => $d['codigo'] ?? '', 'descripcion' => $d['descripcion'] ?? '', 'cantidad' => $cant,
                'precio_unitario' => $pu, 'descuento' => (float) ($d['descuento'] ?? 0), 'total' => $base];
        }
        $total = round($subtotal + $totalIva, 2);

        $html = view('sri.nota-credito', compact('c', 'emisor', 'items', 'subtotal', 'totalIva', 'total'))->render();
        $pdf = Pdf::loadHTML($html)->setPaper('a4');
        $dir = storage_path('app/sri');
        if (! is_dir($dir)) @mkdir($dir, 0775, true);
        $path = $dir . '/NC_' . $c->clave_acceso . '.pdf';
        $pdf->save($path);
        return $path;
    }
}
