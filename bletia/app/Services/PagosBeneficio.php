<?php

namespace App\Services;

use App\Models\PagoBeneficio;
use App\Models\Liquidacion;

class PagosBeneficio
{
    /** Asiento del pago de beneficio: Debe Beneficios por pagar / Haber Banco o Caja. */
    public static function asentar(PagoBeneficio $pago): void
    {
        try {
            if (Contabilidad::yaExiste('PagoBeneficio', $pago->id, 'beneficio')) return;
            $monto = (float) $pago->monto;
            if ($monto <= 0) return;

            $metodo = strtolower((string) ($pago->metodo_pago ?? 'transferencia'));
            $cuentaHaber = in_array($metodo, ['efectivo', 'caja'], true)
                ? ContabilidadAuto::cuenta('beneficio.caja')
                : ContabilidadAuto::cuenta('beneficio.banco');

            Contabilidad::asentar(
                (string) $pago->fecha,
                'Pago ' . (PagoBeneficio::TIPOS[$pago->tipo] ?? $pago->tipo) . ' · ' . ($pago->empleado->nombre ?? ''),
                [
                    ['cuenta' => ContabilidadAuto::cuenta('beneficio.pagar_desde'), 'debe' => $monto, 'detalle' => 'Baja provisión'],
                    ['cuenta' => $cuentaHaber, 'haber' => $monto, 'detalle' => 'Pago ' . $metodo],
                ],
                ['origen' => 'beneficio', 'origen_tipo' => 'PagoBeneficio', 'origen_id' => $pago->id]
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Pago beneficio asiento #' . $pago->id . ': ' . $e->getMessage());
        }
    }

    /** Asiento de liquidación: Debe Beneficios por pagar / Haber Banco. */
    public static function asentarLiquidacion(Liquidacion $liq): void
    {
        try {
            if (Contabilidad::yaExiste('Liquidacion', $liq->id, 'liquidacion')) return;
            $total = (float) $liq->total;
            if ($total <= 0) return;

            // Lo acumulado (13/14/vacaciones/fondos) baja de la provisión ya reconocida cada mes.
            $acumulado = round((float)$liq->decimo_tercero + (float)$liq->decimo_cuarto + (float)$liq->vacaciones + (float)$liq->fondos_reserva + (float)$liq->otros - (float)$liq->descuentos, 2);
            // La indemnización + bonificación NO estaban provisionadas: es gasto nuevo del período.
            $indemniza = round((float)$liq->indemnizacion + (float)$liq->bonificacion_desahucio, 2);

            $lineas = [];
            if ($acumulado > 0) $lineas[] = ['cuenta' => ContabilidadAuto::cuenta('beneficio.pagar_desde'), 'debe' => $acumulado, 'detalle' => 'Beneficios acumulados'];
            if ($indemniza > 0) $lineas[] = ['cuenta' => \App\Services\ContabilidadAuto::cuenta('liquidacion.indemnizacion_gasto'), 'debe' => $indemniza, 'detalle' => 'Indemnización Art. 188 + Art. 185'];
            $lineas[] = ['cuenta' => ContabilidadAuto::cuenta('beneficio.banco'), 'haber' => $total, 'detalle' => 'Pago liquidación'];

            Contabilidad::asentar(
                (string) $liq->fecha,
                'Liquidación de haberes · ' . ($liq->empleado->nombre ?? ''),
                $lineas,
                ['origen' => 'liquidacion', 'origen_tipo' => 'Liquidacion', 'origen_id' => $liq->id]
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Liquidación asiento #' . $liq->id . ': ' . $e->getMessage());
        }
    }
}
