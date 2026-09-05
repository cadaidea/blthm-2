<?php

namespace App\Services;

use App\Models\Incentivo;

class IncentivosContable
{
    /** Asiento: Debe Incentivos (gasto) / Haber Banco o Caja + Retención renta si aplica. */
    public static function asentar(Incentivo $inc): void
    {
        try {
            if (Contabilidad::yaExiste('Incentivo', $inc->id, 'incentivo')) return;
            $monto = (float) $inc->monto;
            if ($monto <= 0) return;

            $metodo = strtolower((string) ($inc->metodo_pago ?? 'transferencia'));
            $cuentaHaber = in_array($metodo, ['efectivo', 'caja'], true)
                ? ContabilidadAuto::cuenta('incentivo.caja')
                : ContabilidadAuto::cuenta('incentivo.banco');

            $lineas = [
                ['cuenta' => ContabilidadAuto::cuenta('incentivo.gasto'), 'debe' => $monto, 'detalle' => 'Incentivo colaborador'],
            ];
            $ret = (float) $inc->ret_renta;
            if ($ret > 0) {
                $lineas[] = ['cuenta' => ContabilidadAuto::cuenta('nomina.ret_renta_pagar'), 'haber' => $ret, 'detalle' => 'Ret. renta'];
            }
            $lineas[] = ['cuenta' => $cuentaHaber, 'haber' => round($monto - $ret, 2), 'detalle' => 'Pago ' . $metodo];

            Contabilidad::asentar(
                (string) $inc->fecha,
                'Incentivo · ' . ($inc->empleado->nombre ?? '') . ' · ' . $inc->concepto,
                $lineas,
                ['origen' => 'incentivo', 'origen_tipo' => 'Incentivo', 'origen_id' => $inc->id]
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Incentivo asiento #' . $inc->id . ': ' . $e->getMessage());
        }
    }
}
