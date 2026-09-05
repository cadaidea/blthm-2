<?php

namespace App\Services;

use App\Models\Cupon;
use App\Models\CuponUso;
use App\Models\Pedido;
use Illuminate\Support\Facades\DB;

class Cupones
{
    /**
     * Valida un código para un cliente y total dado.
     * @return array{ok:bool, cupon:?Cupon, descuento:float, motivo:string}
     */
    public static function validar(?string $codigo, $cliente, float $total): array
    {
        $no = fn ($m) => ['ok' => false, 'cupon' => null, 'descuento' => 0.0, 'motivo' => $m];
        $codigo = strtoupper(trim((string) $codigo));
        if ($codigo === '') return $no('sin código');

        $c = Cupon::where('codigo', $codigo)->where('activo', true)->first();
        if (! $c) return $no('Cupón no válido');
        if ($c->vence_at && $c->vence_at->isPast()) return $no('Cupón vencido');
        if ($c->limite_global !== null && $c->usos >= $c->limite_global) return $no('Cupón agotado');
        if ($c->minimo_compra && $total < (float) $c->minimo_compra) return $no('No alcanza el mínimo de compra');

        // Historial del cliente
        $pagados = $cliente ? Pedido::where('cliente_id', $cliente->id)->where('estado', 'pagado')->count() : 0;

        if ($c->audiencia === 'primera_compra' && $pagados > 0) return $no('Solo para tu primera compra');
        if ($c->audiencia === 'recurrente' && $pagados < 1) return $no('Solo para clientes que ya compraron');

        // ¿ya lo usó este cliente?
        if ($cliente && CuponUso::where('cupon_id', $c->id)->where('cliente_id', $cliente->id)->exists()) {
            return $no('Ya usaste este cupón');
        }

        $desc = $c->tipo === 'porcentaje' ? round($total * (float) $c->valor / 100, 2) : min((float) $c->valor, $total);
        $desc = max(0.0, round($desc, 2));
        if ($desc <= 0) return $no('Descuento no aplicable');

        return ['ok' => true, 'cupon' => $c, 'descuento' => $desc, 'motivo' => 'ok'];
    }

    /** Aplica el descuento (USD) proporcionalmente al array de montos de PayPhone. */
    public static function aplicar(array $m, float $descuento): array
    {
        $total = (float) ($m['total'] ?? 0);
        if ($total <= 0 || $descuento <= 0) return $m;
        $factor = max(0.0, ($total - $descuento) / $total);
        $m['amount']           = (int) round(($m['amount'] ?? 0) * $factor);
        $m['amountWithTax']    = (int) round(($m['amountWithTax'] ?? 0) * $factor);
        $m['amountWithoutTax'] = (int) round(($m['amountWithoutTax'] ?? 0) * $factor);
        $m['tax']              = (int) round(($m['tax'] ?? 0) * $factor);
        // coherencia amount = base+exento+tax
        $m['amount'] = $m['amountWithTax'] + $m['amountWithoutTax'] + $m['tax'];
        $m['subtotal'] = round(($m['subtotal'] ?? 0) * $factor, 2);
        $m['iva']      = round(($m['iva'] ?? 0) * $factor, 2);
        $m['total']    = round($m['amount'] / 100, 2);
        $m['descuento'] = round($descuento, 2);
        return $m;
    }

    /** Registra el uso al confirmar pago (idempotente por unique cupon+cliente). */
    public static function registrarUso(Pedido $pedido): void
    {
        if (! $pedido->cupon_id || ! $pedido->cliente_id) return;
        try {
            CuponUso::firstOrCreate(
                ['cupon_id' => $pedido->cupon_id, 'cliente_id' => $pedido->cliente_id],
                ['pedido_id' => $pedido->id, 'monto' => (float) $pedido->descuento]
            );
            DB::table('cupones')->where('id', $pedido->cupon_id)->increment('usos');
        } catch (\Throwable $e) { report($e); }
    }
}
