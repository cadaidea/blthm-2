<?php

namespace App\Services\Sri;

use App\Models\SriComprobante;
use Barryvdh\DomPDF\Facade\Pdf;

/** Genera el RIDE PDF de una Guía de Remisión. */
class RideGuiaRemision
{
    public static function generar(SriComprobante $c): string
    {
        $emisor = [
            'razon' => Emisor::razon(), 'comercial' => Emisor::nombreComercial(), 'ruc' => Emisor::ruc(),
            'dir_matriz' => Emisor::dirMatriz(), 'obligado' => Emisor::obligadoContabilidad(),
        ];
        $html = view('sri.guia-remision', compact('c', 'emisor'))->render();
        $pdf = Pdf::loadHTML($html)->setPaper('a4');
        $dir = storage_path('app/sri');
        if (! is_dir($dir)) @mkdir($dir, 0775, true);
        $path = $dir . '/GUIA_' . $c->clave_acceso . '.pdf';
        $pdf->save($path);
        return $path;
    }
}
