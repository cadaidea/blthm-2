<?php

namespace App\Services\Sri;

use Illuminate\Support\Facades\DB;

/**
 * Maneja la lógica de facturación a crédito.
 * El SRI declara el saldo a crédito como forma de pago código 20 (otros con SF)
 * con plazo y unidadTiempo en días. Lo ya pagado va con sus formas reales.
 */
class Credito
{
    /**
     * Construye las formas de pago para una factura considerando crédito.
     * - Lo pagado (recibos validados) → sus formas reales.
     * - El saldo pendiente → forma 20 a crédito con plazo en días.
     *
     * @return array [['forma','monto','plazo'(opcional),'unidad'(opcional)], ...]
     */
    public static function pagosConCredito($pedido, float $total, bool $esCredito, ?int $plazoDias): array
    {
        $pagados = FormasPago::dePedido($pedido); // formas reales de lo cobrado
        $sumaPagada = array_sum(array_column($pagados, 'monto'));
        $saldo = round($total - $sumaPagada, 2);

        if (! $esCredito || $saldo <= 0.005) {
            // contado: solo lo pagado (o el total si no hay recibos)
            return $pagados ?: [['forma' => '01', 'monto' => $total]];
        }

        // crédito: lo pagado con sus formas + el saldo a crédito (forma 20 con plazo)
        $out = $pagados;
        $out[] = [
            'forma' => '20',
            'monto' => $saldo,
            'plazo' => max(1, (int) ($plazoDias ?? 30)),
            'unidad' => 'dias',
        ];
        return $out;
    }

    /** Calcula la fecha de vencimiento desde hoy + plazo en días. */
    public static function vencimiento(int $plazoDias): string
    {
        return now()->addDays(max(1, $plazoDias))->toDateString();
    }

    /** Calcula los días entre hoy y una fecha límite. */
    public static function diasHasta(string $fecha): int
    {
        return max(1, now()->startOfDay()->diffInDays(\Illuminate\Support\Carbon::parse($fecha)->startOfDay(), false));
    }
}
