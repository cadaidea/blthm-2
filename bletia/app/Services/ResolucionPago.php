<?php
namespace App\Services;

use App\Mail\DocumentoPedido;
use App\Support\CorreoBrand;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Resuelve el destino de un pago validado cuando su pedido fue anulado:
 * nota_credito | saldo_favor | reembolso. Registra y notifica.
 */
class ResolucionPago
{
    public static function resolver($recibo, string $tipo, ?string $nota = null): array
    {
        $ped = DB::table('pedidos')->where('id', $recibo->pedido_id)->first();
        $cli = $recibo->cliente_id ? DB::table('clientes')->where('id', $recibo->cliente_id)->first() : null;
        $monto = (float) $recibo->monto;
        $num = $ped->folio ?? $recibo->pedido_id;

        DB::table('recibos')->where('id', $recibo->id)->update([
            'resolucion' => $tipo, 'resuelto_por' => auth()->id(), 'resuelto_at' => now(),
        ]);

        // saldo a favor: acumula crédito al cliente
        if ($tipo === 'saldo_favor' && $cli) {
            DB::table('clientes')->where('id', $cli->id)->update([
                'saldo_favor' => (float) ($cli->saldo_favor ?? 0) + $monto,
            ]);
        }

        $labels = ['nota_credito' => 'Nota de crédito', 'saldo_favor' => 'Saldo a favor', 'reembolso' => 'Reembolso'];
        $label = $labels[$tipo] ?? $tipo;

        // notificar contabilidad
        $contab = class_exists(\App\Models\Ajuste::class) ? (\App\Models\Ajuste::get('erp_email_contabilidad') ?: null) : null;
        if ($contab) {
            $cuerpo = '<p>Resolución de pago del pedido anulado <strong>' . $num . '</strong>.</p>'
                . '<p><strong>Tipo:</strong> ' . $label . ' · <strong>Monto:</strong> $' . number_format($monto, 2) . '</p>'
                . '<p><strong>Recibo:</strong> ' . ($recibo->folio ?? '—') . ' · <strong>Método original:</strong> ' . ucfirst((string) $recibo->metodo) . '</p>'
                . ($nota ? '<p>Nota: ' . e($nota) . '</p>' : '');
            $html = CorreoBrand::wrap('Resolución de pago · ' . $num, $cuerpo);
            try { Mail::to($contab)->send(new DocumentoPedido('Resolución de pago · ' . $num, $html, [])); } catch (\Throwable $e) { report($e); }
        }

        // notificar cliente (saldo a favor / reembolso)
        if ($cli && ! empty($cli->email) && in_array($tipo, ['saldo_favor', 'reembolso'], true)) {
            if ($tipo === 'saldo_favor') {
                $cuerpo = '<p>Tu pago de <strong>$' . number_format($monto, 2) . '</strong> del pedido anulado <strong>' . $num . '</strong> quedó como <strong>saldo a favor</strong> para futuras compras.</p>';
            } else {
                $cuerpo = '<p>Procesaremos el <strong>reembolso</strong> de <strong>$' . number_format($monto, 2) . '</strong> correspondiente al pedido anulado <strong>' . $num . '</strong>.</p>';
            }
            $html = CorreoBrand::wrap($label, $cuerpo);
            try { Mail::to($cli->email)->send(new DocumentoPedido($label . ' · pedido ' . $num, $html, [])); } catch (\Throwable $e) { report($e); }
        }

        return ['ok' => true, 'tipo' => $label];
    }
}
