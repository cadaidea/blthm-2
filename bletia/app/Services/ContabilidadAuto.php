<?php

namespace App\Services;

use App\Models\CuentaMapeo;
use App\Models\Cuenta;
use Illuminate\Support\Facades\Cache;

/**
 * Traduce eventos del ERP en asientos contables usando la tabla cuenta_mapeos.
 * Idempotente (no duplica por origen) y silencioso: si algo falta, registra y sigue,
 * NUNCA rompe la operación del ERP (una venta no debe fallar porque falte una cuenta).
 */
class ContabilidadAuto
{
    /** Código de cuenta para una clave de mapeo (ej 'venta.cxc'). */
    public static function cuenta(string $clave): ?string
    {
        $map = Cache::remember('cuenta_mapeos_all', 3600, fn () => CuentaMapeo::pluck('codigo_cuenta', 'clave')->toArray());
        return $map[$clave] ?? null;
    }

    public static function olvidar(): void { Cache::forget('cuenta_mapeos_all'); }

    /** Envuelve asentar() capturando cualquier error para no romper el ERP. */
    protected static function intentar(callable $fn, string $ctx): void
    {
        try { $fn(); }
        catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Contabilidad auto [$ctx]: " . $e->getMessage());
            // Visible para el admin en Auditoría (bitácora) — antes solo quedaba en el log.
            \App\Models\Bitacora::registrar(
                'contabilidad_fallo',
                'ContabilidadAuto',
                null,
                "[{$ctx}] " . $e->getMessage()
            );
        }
    }

    // ---------- VENTA facturada ----------
    public static function venta($venta): void
    {
        self::intentar(function () use ($venta) {
            if (Contabilidad::yaExiste('Venta', $venta->id, 'venta')) return;
            $base = (float) $venta->subtotal; $iva = (float) $venta->iva; $total = (float) $venta->total;
            if ($total <= 0) return;

            $lineas = [
                ['cuenta' => self::cuenta('venta.cxc'), 'debe' => $total, 'detalle' => 'Cliente'],
                ['cuenta' => self::cuenta('venta.ingreso'), 'haber' => $base, 'detalle' => 'Ventas'],
            ];
            if ($iva > 0) $lineas[] = ['cuenta' => self::cuenta('venta.iva'), 'haber' => $iva, 'detalle' => 'IVA ventas'];

            Contabilidad::asentar(
                (string) ($venta->fecha ?? now()->toDateString()),
                'Venta ' . ($venta->nro_factura ?: $venta->folio ?: ('#' . $venta->id)),
                $lineas,
                ['origen' => 'venta', 'origen_tipo' => 'Venta', 'origen_id' => $venta->id]
            );
        }, 'venta#' . ($venta->id ?? '?'));
    }

    // ---------- COBRO (recibo de cliente) ----------
    public static function cobro($recibo): void
    {
        self::intentar(function () use ($recibo) {
            if (Contabilidad::yaExiste('Recibo', $recibo->id, 'cobro')) return;
            $monto = (float) $recibo->monto;
            if ($monto <= 0) return;
            // Solo cobros a clientes (con pedido/venta/cliente). Ignora otros usos del recibo.
            if (empty($recibo->cliente_id) && empty($recibo->pedido_id) && empty($recibo->venta_id)) return;

            $metodo = strtolower((string) ($recibo->metodo ?? 'efectivo'));
            $cuentaDebe = self::cuenta('cobro.' . $metodo) ?: self::cuenta('cobro.efectivo');

            Contabilidad::asentar(
                (string) ($recibo->fecha ?? now()->toDateString()),
                'Cobro ' . ($recibo->folio ?: ('#' . $recibo->id)) . ' · ' . $metodo,
                [
                    ['cuenta' => $cuentaDebe, 'debe' => $monto, 'detalle' => 'Ingreso ' . $metodo],
                    ['cuenta' => self::cuenta('cobro.cxc'), 'haber' => $monto, 'detalle' => 'Baja CxC'],
                ],
                ['origen' => 'cobro', 'origen_tipo' => 'Recibo', 'origen_id' => $recibo->id]
            );
        }, 'cobro#' . ($recibo->id ?? '?'));
    }

    // ---------- COMPRA recibida (inventario) ----------
    public static function compra($compra): void
    {
        self::intentar(function () use ($compra) {
            if (Contabilidad::yaExiste('Compra', $compra->id, 'compra')) return;
            $base = (float) $compra->subtotal; $iva = (float) $compra->iva; $total = (float) $compra->total;
            if ($total <= 0) return;

            $lineas = [
                ['cuenta' => self::cuenta('compra.inventario'), 'debe' => $base, 'detalle' => 'Inventario'],
            ];
            if ($iva > 0) $lineas[] = ['cuenta' => self::cuenta('compra.iva'), 'debe' => $iva, 'detalle' => 'IVA crédito'];
            $lineas[] = ['cuenta' => self::cuenta('compra.cxp'), 'haber' => round($base + $iva, 2), 'detalle' => 'Proveedor'];

            Contabilidad::asentar(
                (string) ($compra->doc_fecha ?? $compra->recibida_at ?? now()->toDateString()),
                'Compra ' . ($compra->folio ?: ('#' . $compra->id)),
                $lineas,
                ['origen' => 'compra', 'origen_tipo' => 'Compra', 'origen_id' => $compra->id]
            );
        }, 'compra#' . ($compra->id ?? '?'));
    }

    // ---------- PAGO a proveedor ----------
    public static function pagoProveedor($pago): void
    {
        self::intentar(function () use ($pago) {
            if (Contabilidad::yaExiste('CompraPago', $pago->id, 'pago')) return;
            $monto = (float) $pago->monto;
            if ($monto <= 0) return;
            $metodo = strtolower((string) ($pago->metodo ?? 'transferencia'));
            $cuentaHaber = self::cuenta('pago.' . $metodo) ?: self::cuenta('pago.transferencia');

            Contabilidad::asentar(
                (string) ($pago->fecha ?? now()->toDateString()),
                'Pago proveedor · compra #' . $pago->compra_id . ' · ' . $metodo,
                [
                    ['cuenta' => self::cuenta('pago.cxp'), 'debe' => $monto, 'detalle' => 'Baja CxP'],
                    ['cuenta' => $cuentaHaber, 'haber' => $monto, 'detalle' => 'Egreso ' . $metodo],
                ],
                ['origen' => 'pago', 'origen_tipo' => 'CompraPago', 'origen_id' => $pago->id]
            );
        }, 'pago#' . ($pago->id ?? '?'));
    }

    // ---------- GASTO ----------
    public static function gasto($gasto): void
    {
        self::intentar(function () use ($gasto) {
            if (Contabilidad::yaExiste('Gasto', $gasto->id, 'gasto')) return;
            $base = (float) $gasto->base; $iva = (float) $gasto->iva;
            $total = round($base + $iva, 2);
            if ($total <= 0) return;

            $cuentaGasto = self::cuenta('gasto.' . $gasto->categoria) ?: self::cuenta('gasto.varios');
            $lineas = [['cuenta' => $cuentaGasto, 'debe' => $base, 'detalle' => $gasto->categoria]];
            if ($iva > 0) $lineas[] = ['cuenta' => self::cuenta('gasto.iva'), 'debe' => $iva, 'detalle' => 'IVA crédito'];

            if (($gasto->forma_pago ?? 'contado') === 'credito') {
                $lineas[] = ['cuenta' => self::cuenta('gasto.cxp'), 'haber' => $total, 'detalle' => 'Por pagar'];
            } else {
                $metodo = strtolower((string) ($gasto->metodo_pago ?? 'efectivo'));
                $cuentaHaber = self::cuenta('pago_gasto.' . $metodo) ?: self::cuenta('pago_gasto.efectivo');
                $lineas[] = ['cuenta' => $cuentaHaber, 'haber' => $total, 'detalle' => 'Pago ' . $metodo];
            }

            Contabilidad::asentar(
                (string) ($gasto->fecha ?? now()->toDateString()),
                'Gasto ' . ($gasto->folio ?: ('#' . $gasto->id)) . ' · ' . ($gasto->categoria),
                $lineas,
                ['origen' => 'gasto', 'origen_tipo' => 'Gasto', 'origen_id' => $gasto->id]
            );
        }, 'gasto#' . ($gasto->id ?? '?'));
    }

    /** Reversa el asiento de un documento anulado. */
    public static function reversarDe(string $origenTipo, int $origenId): void
    {
        self::intentar(function () use ($origenTipo, $origenId) {
            $asiento = \App\Models\Asiento::where('origen_tipo', $origenTipo)
                ->where('origen_id', $origenId)->where('estado', 'registrado')->first();
            if ($asiento) Contabilidad::reversar($asiento);
        }, "reverso $origenTipo#$origenId");
    }
}
