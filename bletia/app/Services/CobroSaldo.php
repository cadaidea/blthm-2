<?php
namespace App\Services;

use App\Mail\DocumentoPedido;
use App\Support\CorreoBrand;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CobroSaldo
{
    public static function solicitar($pedido): void
    {
        if (! $pedido) return;
        try {
            $saldo = RecibosErp::saldo($pedido);
            if ($saldo <= 0) {
                \Filament\Notifications\Notification::make()->info()->title('Sin saldo pendiente')->send();
                return;
            }
            $folio = $pedido->folio ?? ('#' . $pedido->id);
            $cli = $pedido->cliente_id ? DB::table('clientes')->where('id', $pedido->cliente_id)->value('nombre') : '';
            $cuerpo = '<p>El pedido <strong>' . $folio . '</strong>' . ($cli ? ' (' . e($cli) . ')' : '')
                . ' tiene un saldo pendiente de <strong>$' . number_format($saldo, 2) . '</strong>.</p>'
                . '<p>Por favor gestionar el cobro y registrar/validar el pago para poder despachar.</p>';
            $html = CorreoBrand::wrap('Cobro de saldo pendiente · ' . $folio, $cuerpo);

            $dest = [];
            if (! empty($pedido->vendedor_id)) {
                $e = DB::table('users')->where('id', $pedido->vendedor_id)->value('email');
                if ($e) $dest[] = $e;
            }
            foreach (DB::table('users')->whereIn('rol', ['operaciones', 'contabilidad', 'admin'])->where('activo', true)->pluck('email') as $e) $dest[] = $e;

            foreach (array_unique(array_filter($dest)) as $to) {
                try { Mail::to($to)->send(new DocumentoPedido('Cobro de saldo · ' . $folio, $html, [])); } catch (\Throwable $e) { report($e); }
            }
            \App\Models\Bitacora::registrar('solicitó cobro', 'Pedido', $pedido->id, $folio . ' · $' . number_format($saldo, 2));
            \Filament\Notifications\Notification::make()->success()->title('Cobro solicitado')->body('Se notificó a los responsables.')->send();
        } catch (\Throwable $e) { report($e); }
    }

    public static function recordatorios(): int
    {
        $n = 0;
        foreach (DB::table('pedidos')->whereIn('estado_erp', ['en_bodega', 'listo_despacho'])->get() as $row) {
            $p = \App\Models\PedidoEspecial::find($row->id);
            if ($p && RecibosErp::saldo($p) > 0) { self::solicitar($p); $n++; }
        }
        return $n;
    }
}
