<?php

namespace App\Services\Sri;

use App\Models\Pedido;
use Illuminate\Support\Facades\DB;

/**
 * Deriva las formas de pago SRI de un pedido a partir de su historial de recibos validados.
 * Agrupa por código SRI y suma montos, para que la factura declare exactamente lo pagado.
 *
 * Tabla 24 oficial del SRI (formas de pago vigentes):
 *  01 Sin utilización del sistema financiero (efectivo)
 *  15 Compensación de deudas
 *  16 Tarjeta de débito
 *  17 Dinero electrónico
 *  18 Tarjeta prepago
 *  19 Tarjeta de crédito
 *  20 Otros con utilización del sistema financiero (transferencia, cheque, depósito)
 *  21 Endoso de títulos
 */
class FormasPago
{
    /** Catálogo oficial Tabla 24. */
    public const CATALOGO = [
        '01' => 'Sin utilización del sistema financiero (efectivo)',
        '15' => 'Compensación de deudas',
        '16' => 'Tarjeta de débito',
        '17' => 'Dinero electrónico',
        '18' => 'Tarjeta prepago',
        '19' => 'Tarjeta de crédito',
        '20' => 'Otros con utilización del sistema financiero (transferencia, cheque, depósito)',
        '21' => 'Endoso de títulos',
    ];

    /** Mapea un método de recibo (+ tipo de tarjeta) a código SRI oficial. */
    public static function mapear(string $metodo, ?string $tipoTarjeta = null, ?string $naturaleza = null): string
    {
        $metodo = strtolower(trim($metodo));
        return match ($metodo) {
            'efectivo' => '01',
            'transferencia', 'deposito', 'cheque' => '20',
            'tarjeta' => strtolower((string) $naturaleza) === 'credito' ? '19' : '16',
            'credito' => '19',
            default => '01', // 'otro' y cualquier no mapeado
        };
    }

    /** Etiqueta legible de una forma de pago SRI. */
    public static function etiqueta(string $codigo): string
    {
        return self::CATALOGO[$codigo] ?? ('Forma ' . $codigo);
    }

    /**
     * Formas de pago del pedido desde sus recibos validados.
     * Formato: [['forma'=>'01','monto'=>200.00,'etiqueta'=>'...'], ...]
     * Vacío si no hay recibos validados (el llamador decide el fallback).
     */
    public static function dePedido($pedido): array
    {
        $recibos = DB::table('recibos')
            ->where('pedido_id', $pedido->id)
            ->where('validado', 1)
            ->get(['metodo', 'tipo_tarjeta', 'tarjeta_naturaleza', 'monto']);

        $acc = [];
        foreach ($recibos as $r) {
            $cod = self::mapear((string) $r->metodo, $r->tipo_tarjeta ?? null, $r->tarjeta_naturaleza ?? null);
            $acc[$cod] = ($acc[$cod] ?? 0) + (float) $r->monto;
        }

        $out = [];
        foreach ($acc as $cod => $monto) {
            $out[] = ['forma' => $cod, 'monto' => round($monto, 2), 'etiqueta' => self::etiqueta($cod)];
        }
        return $out;
    }

    /**
     * Desglose de formas de pago para MOSTRAR en comprobantes (factura/nota).
     * Agrupa por código SRI + bloque por método real, con el detalle de cada pago.
     * Estructura:
     * [
     *   ['codigo'=>'20','etiqueta'=>'Otros con utilización...','metodo'=>'Transferencia','total'=>399.00,
     *    'pagos'=>[['label'=>'Transferencia','monto'=>200],['label'=>'Transferencia','monto'=>199]]],
     *   ...
     * ]
     */
    public static function desglosePedido($pedido): array
    {
        $recibos = DB::table('recibos')
            ->where('pedido_id', $pedido->id)
            ->where('validado', 1)
            ->orderBy('fecha')
            ->get(['metodo', 'tipo_tarjeta', 'tarjeta_naturaleza', 'monto', 'cheque_numero', 'cheque_banco', 'nro_comprobante']);

        $labels = [
            'efectivo' => 'Efectivo', 'transferencia' => 'Transferencia',
            'tarjeta' => 'Tarjeta', 'deposito' => 'Depósito', 'cheque' => 'Cheque', 'otro' => 'Otro',
        ];

        // agrupar por (código SRI + método real), preservando bloques separados
        $grupos = [];
        foreach ($recibos as $r) {
            $m = strtolower((string) $r->metodo);
            $cod = self::mapear($m, $r->tipo_tarjeta ?? null, $r->tarjeta_naturaleza ?? null);
            $metodoLabel = $labels[$m] ?? ucfirst($m);
            if ($m === 'tarjeta' && $r->tarjeta_naturaleza) {
                $metodoLabel = 'Tarjeta ' . $r->tarjeta_naturaleza;
            }
            $key = $cod . '|' . $metodoLabel;
            if (! isset($grupos[$key])) {
                $grupos[$key] = ['codigo' => $cod, 'etiqueta' => self::etiqueta($cod), 'metodo' => $metodoLabel, 'total' => 0, 'pagos' => []];
            }
            // detalle del pago individual
            $det = $metodoLabel;
            if ($m === 'cheque' && $r->cheque_numero) $det .= ' N° ' . $r->cheque_numero;
            elseif (in_array($m, ['transferencia','deposito']) && $r->nro_comprobante) $det .= ' #' . $r->nro_comprobante;
            $grupos[$key]['pagos'][] = ['label' => $det, 'monto' => round((float) $r->monto, 2)];
            $grupos[$key]['total'] += (float) $r->monto;
        }

        // redondear totales
        foreach ($grupos as &$g) $g['total'] = round($g['total'], 2);
        return array_values($grupos);
    }
    /** Igual que desglosePedido() pero para una VENTA DIRECTA (sin pedido). Incluye pendientes de validar, marcados. */
    public static function desglosePorVenta($venta): array
    {
        $recibos = DB::table('recibos')
            ->where('venta_id', $venta->id)
            ->orderBy('fecha')
            ->get(['metodo', 'tipo_tarjeta', 'tarjeta_naturaleza', 'monto', 'cheque_numero', 'cheque_banco', 'nro_comprobante', 'validado']);
        $labels = [
            'efectivo' => 'Efectivo', 'transferencia' => 'Transferencia',
            'tarjeta' => 'Tarjeta', 'deposito' => 'Depósito', 'cheque' => 'Cheque', 'otro' => 'Otro',
        ];
        $grupos = [];
        foreach ($recibos as $r) {
            $m = strtolower((string) $r->metodo);
            $cod = self::mapear($m, $r->tipo_tarjeta ?? null, $r->tarjeta_naturaleza ?? null);
            $metodoLabel = $labels[$m] ?? ucfirst($m);
            if ($m === 'tarjeta' && $r->tarjeta_naturaleza) $metodoLabel = 'Tarjeta ' . $r->tarjeta_naturaleza;
            $key = $cod . '|' . $metodoLabel;
            if (! isset($grupos[$key])) {
                $grupos[$key] = ['codigo' => $cod, 'etiqueta' => self::etiqueta($cod), 'metodo' => $metodoLabel, 'total' => 0, 'pagos' => []];
            }
            $det = $metodoLabel;
            if ($m === 'cheque' && $r->cheque_numero) $det .= ' N° ' . $r->cheque_numero;
            elseif (in_array($m, ['transferencia','deposito']) && $r->nro_comprobante) $det .= ' #' . $r->nro_comprobante;
            if (! ($r->validado ?? true)) $det .= ' (pendiente de validar)';
            $grupos[$key]['pagos'][] = ['label' => $det, 'monto' => round((float) $r->monto, 2)];
            $grupos[$key]['total'] += (float) $r->monto;
        }
        foreach ($grupos as &$g) $g['total'] = round($g['total'], 2);
        return array_values($grupos);
    }

    /** Para el XML del SRI: agrupado por código (paralelo a dePedido, sin tocarlo). */
    public static function deVenta($venta): array
    {
        $recibos = DB::table('recibos')->where('venta_id', $venta->id)->where('validado', 1)
            ->get(['metodo', 'tipo_tarjeta', 'tarjeta_naturaleza', 'monto']);
        $acc = [];
        foreach ($recibos as $r) {
            $cod = self::mapear((string) $r->metodo, $r->tipo_tarjeta ?? null, $r->tarjeta_naturaleza ?? null);
            $acc[$cod] = ($acc[$cod] ?? 0) + (float) $r->monto;
        }
        $out = [];
        foreach ($acc as $cod => $monto) $out[] = ['forma' => $cod, 'monto' => round($monto, 2), 'etiqueta' => self::etiqueta($cod)];
        return $out;
    }
    /** Total pagado (suma de recibos validados). */
    public static function totalPagado($pedido): float
    {
        return round((float) DB::table('recibos')->where('pedido_id', $pedido->id)->where('validado', 1)->sum('monto'), 2);
    }
}
