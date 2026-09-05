<?php

namespace App\Services;

use App\Mail\DocumentoPedido;
use App\Support\CorreoBrand;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/**
 * Aviso de tesorería: cheques pendientes próximos a vencer o vencidos sin cobrar.
 * Se ejecuta a diario por el scheduler. Notifica a tesorería para no olvidar depositarlos.
 */
class ChequesAviso
{
    /**
     * Envía un correo con los cheques pendientes que vencen dentro de N días o ya vencieron.
     * @param int $dias  ventana de anticipación (por defecto 3 días)
     * @return int  cantidad de cheques avisados
     */
    public static function porVencer(int $dias = 3): int
    {
        if (! Schema::hasTable('recibos')) return 0;

        $limite = now()->addDays($dias)->toDateString();

        $cheques = DB::table('recibos')
            ->where('metodo', 'cheque')
            ->where('cheque_cobrado', false)
            ->whereNotIn('cheque_estado', ['anulado', 'rechazado', 'cobrado'])
            ->whereNotNull('cheque_fecha_cobro')
            ->whereDate('cheque_fecha_cobro', '<=', $limite)
            ->orderBy('cheque_fecha_cobro')
            ->get(['id', 'pedido_id', 'cheque_numero', 'cheque_banco', 'cheque_girador', 'cheque_fecha_cobro', 'monto']);

        if ($cheques->isEmpty()) return 0;

        // armar tabla del correo
        $filas = '';
        $totalMonto = 0.0;
        foreach ($cheques as $ch) {
            $ped = DB::table('pedidos')->where('id', $ch->pedido_id)->value('folio') ?: ('#' . $ch->pedido_id);
            $fecha = \Illuminate\Support\Carbon::parse($ch->cheque_fecha_cobro);
            $hoy = now()->startOfDay();
            $diff = $hoy->diffInDays($fecha->copy()->startOfDay(), false);
            $estado = $diff < 0 ? ('Vencido hace ' . abs($diff) . ' día(s)') : ($diff === 0 ? 'Vence HOY' : ('Faltan ' . $diff . ' día(s)'));
            $color = $diff <= 0 ? '#c0392b' : ($diff <= 1 ? '#b8860b' : '#5b6470');
            $totalMonto += (float) $ch->monto;
            $filas .= '<tr>'
                . '<td style="padding:6px 10px; border-bottom:1px solid #eee;">' . $fecha->format('d/m/Y') . '<br><span style="color:' . $color . '; font-size:12px;">' . $estado . '</span></td>'
                . '<td style="padding:6px 10px; border-bottom:1px solid #eee;">' . e($ch->cheque_numero ?: '—') . '</td>'
                . '<td style="padding:6px 10px; border-bottom:1px solid #eee;">' . e($ch->cheque_banco ?: '—') . '</td>'
                . '<td style="padding:6px 10px; border-bottom:1px solid #eee;">' . e($ch->cheque_girador ?: '—') . '</td>'
                . '<td style="padding:6px 10px; border-bottom:1px solid #eee;">' . e($ped) . '</td>'
                . '<td style="padding:6px 10px; border-bottom:1px solid #eee; text-align:right; font-weight:bold;">$' . number_format((float) $ch->monto, 2) . '</td>'
                . '</tr>';
        }

        $cuerpo = '<p>Hay <strong>' . $cheques->count() . '</strong> cheque(s) pendiente(s) de cobro por un total de <strong>$' . number_format($totalMonto, 2) . '</strong>.</p>'
            . '<table style="width:100%; border-collapse:collapse; font-size:13px; margin-top:10px;">'
            . '<thead><tr style="background:#161921; color:#fff;">'
            . '<th style="padding:6px 10px; text-align:left;">Fecha de cobro</th>'
            . '<th style="padding:6px 10px; text-align:left;">N° cheque</th>'
            . '<th style="padding:6px 10px; text-align:left;">Banco</th>'
            . '<th style="padding:6px 10px; text-align:left;">Girador</th>'
            . '<th style="padding:6px 10px; text-align:left;">Pedido</th>'
            . '<th style="padding:6px 10px; text-align:right;">Monto</th>'
            . '</tr></thead><tbody>' . $filas . '</tbody></table>'
            . '<p style="margin-top:12px;">Recuerda depositarlos a tiempo. Al cobrarlos, márcalos en <strong>Cheques por cobrar</strong>.</p>';

        $html = CorreoBrand::wrap('Cheques por cobrar · recordatorio', $cuerpo);

        // destinatarios: tesorería (mismo patrón que CobroSaldo)
        $dest = DB::table('users')->whereIn('rol', ['admin', 'contabilidad', 'operaciones'])->where('activo', true)->pluck('email')->all();
        foreach (array_unique(array_filter($dest)) as $to) {
            try { Mail::to($to)->send(new DocumentoPedido('Cheques por cobrar · recordatorio', $html, [])); } catch (\Throwable $e) { report($e); }
        }

        return $cheques->count();
    }
}
