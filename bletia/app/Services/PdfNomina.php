<?php

namespace App\Services;

use App\Models\Liquidacion;
use App\Models\PagoBeneficio;
use App\Models\RolPago;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfNomina
{
    protected static function dir(): string
    {
        $dir = storage_path('app/nomina-pdf');
        if (! is_dir($dir)) @mkdir($dir, 0775, true);
        return $dir;
    }

    /** PDF del rol de pago (rol individual del empleado). */
    public static function rol(RolPago $rol): string
    {
        $rol->loadMissing('empleado');
        $empresa = PdfErp::empresa();
        $html = view('nomina.pdf-rol', compact('rol', 'empresa'))->render();
        $pdf = Pdf::loadHTML($html)->setPaper('a4');
        $path = self::dir() . '/ROL_' . ($rol->folio ?: $rol->id) . '.pdf';
        $pdf->save($path);
        return $path;
    }

    /** PDF de solicitud/autorización de vacaciones. */
    public static function vacacion(\App\Models\VacacionTomada $vac): string
    {
        $vac->loadMissing('empleado');
        $empresa = PdfErp::empresa();
        $pendientesDespues = \App\Services\VacacionesControl::diasPendientes($vac->empleado, $vac->fecha_fin);
        $html = view('nomina.pdf-vacacion', compact('vac', 'empresa', 'pendientesDespues'))->render();
        $pdf = Pdf::loadHTML($html)->setPaper('a4');
        $path = self::dir() . '/VAC_' . ($vac->folio ?: $vac->id) . '.pdf';
        $pdf->save($path);
        return $path;
    }

    /** PDF de comprobante de incentivo a colaborador. */
    public static function incentivo(\App\Models\Incentivo $inc): string
    {
        $inc->loadMissing('empleado');
        $empresa = PdfErp::empresa();
        $html = view('nomina.pdf-incentivo', compact('inc', 'empresa'))->render();
        $pdf = Pdf::loadHTML($html)->setPaper('a4');
        $path = self::dir() . '/INC_' . ($inc->folio ?: $inc->id) . '.pdf';
        $pdf->save($path);
        return $path;
    }

    /** PDF de la liquidación de haberes. */
    public static function liquidacion(Liquidacion $liq): string
    {
        $liq->loadMissing('empleado');
        $empresa = PdfErp::empresa();
        $html = view('nomina.pdf-liquidacion', compact('liq', 'empresa'))->render();
        $pdf = Pdf::loadHTML($html)->setPaper('a4');
        $path = self::dir() . '/LIQ_' . ($liq->folio ?: $liq->id) . '.pdf';
        $pdf->save($path);
        return $path;
    }

    /** PDF del pago de beneficio (recibo). */
    public static function beneficio(PagoBeneficio $pago): string
    {
        $pago->loadMissing('empleado');
        $empresa = PdfErp::empresa();
        $html = view('nomina.pdf-beneficio', compact('pago', 'empresa'))->render();
        $pdf = Pdf::loadHTML($html)->setPaper('a4');
        $path = self::dir() . '/BEN_' . ($pago->folio ?: $pago->id) . '.pdf';
        $pdf->save($path);
        return $path;
    }
}
