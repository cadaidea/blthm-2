<?php

namespace App\Services;

use App\Mail\DocumentoPedido;
use App\Models\MateriaPrima;
use App\Support\CorreoBrand;
use Illuminate\Support\Facades\Mail;

/**
 * Aviso diario: materias primas en (o bajo) su stock mínimo.
 * Para no frenar producción por falta de material.
 */
class MateriaPrimaAviso
{
    public static function bajoMinimo(): int
    {
        $items = MateriaPrima::where('activo', true)->get()->filter(fn ($m) => $m->bajoMinimo());
        if ($items->isEmpty()) return 0;

        $filas = '';
        foreach ($items as $m) {
            $filas .= '<tr>'
                . '<td style="padding:6px 10px; border-bottom:1px solid #eee;">' . e($m->nombre) . '</td>'
                . '<td style="padding:6px 10px; border-bottom:1px solid #eee; text-align:right; color:#c0392b; font-weight:bold;">' . number_format((float) $m->stock, 2) . ' ' . e($m->unidad) . '</td>'
                . '<td style="padding:6px 10px; border-bottom:1px solid #eee; text-align:right;">' . number_format((float) $m->minimo, 2) . ' ' . e($m->unidad) . '</td>'
                . '</tr>';
        }

        $cuerpo = '<p>Hay <strong>' . $items->count() . '</strong> materia(s) prima(s) en o por debajo del stock mínimo.</p>'
            . '<table style="width:100%; border-collapse:collapse; font-size:13px; margin-top:10px;">'
            . '<thead><tr style="background:#161921; color:#fff;">'
            . '<th style="padding:6px 10px; text-align:left;">Material</th>'
            . '<th style="padding:6px 10px; text-align:right;">Stock actual</th>'
            . '<th style="padding:6px 10px; text-align:right;">Mínimo</th>'
            . '</tr></thead><tbody>' . $filas . '</tbody></table>'
            . '<p style="margin-top:12px;">Revisa y reabastece para no frenar producción.</p>';

        $html = CorreoBrand::wrap('Materia prima por agotarse', $cuerpo);

        $dest = \Illuminate\Support\Facades\DB::table('users')->whereIn('rol', ['admin', 'operaciones', 'produccion'])->where('activo', true)->pluck('email')->all();
        foreach (array_unique(array_filter($dest)) as $to) {
            try { Mail::to($to)->send(new DocumentoPedido('Materia prima por agotarse', $html, [])); } catch (\Throwable $e) { report($e); }
        }

        return $items->count();
    }
}
