<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class PdfContable
{
    protected static function dir(): string
    {
        $dir = storage_path('app/contable-pdf');
        if (! is_dir($dir)) @mkdir($dir, 0775, true);
        return $dir;
    }

    /** Comprobante de egreso por gasto (con 3 firmas). */
    public static function gasto($gasto): string
    {
        $gasto->loadMissing('proveedor');
        $empresa = PdfErp::empresa();
        $asiento = \App\Models\Asiento::where('origen_tipo', 'Gasto')
            ->where('origen_id', $gasto->id)->where('estado', 'registrado')->first();
        $html = view('contable.pdf-gasto', compact('gasto', 'empresa', 'asiento'))->render();
        $path = self::dir() . '/GASTO_' . ($gasto->folio ?: $gasto->id) . '.pdf';
        Pdf::loadHTML($html)->setPaper('a4')->save($path);
        return $path;
    }

    /** Comprobante contable (asiento de diario) con 3 firmas. */
    public static function asiento($asiento): string
    {
        $asiento->loadMissing('lineas.cuenta');
        $empresa = PdfErp::empresa();
        $html = view('contable.pdf-asiento', compact('asiento', 'empresa'))->render();
        $path = self::dir() . '/AST_' . ($asiento->numero ?: $asiento->id) . '.pdf';
        Pdf::loadHTML($html)->setPaper('a4')->save($path);
        return $path;
    }
}
