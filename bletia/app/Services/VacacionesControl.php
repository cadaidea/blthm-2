<?php

namespace App\Services;

use App\Models\Empleado;
use App\Models\VacacionTomada;
use Illuminate\Support\Carbon;

/**
 * Control de vacaciones por DÍAS (Art. 69 Código de Trabajo: 15 días calendario mínimo,
 * período ininterrumpido). Independiente de la provisión en dólares que ya se calcula
 * en cada rol de pago (esa es para contabilidad; esta es para cumplimiento/documentación).
 */
class VacacionesControl
{
    /** Días ganados desde el ingreso hasta una fecha, proporcional a los meses trabajados. */
    public static function diasGanados(Empleado $emp, string $hasta): float
    {
        if (! $emp->fecha_ingreso) return 0.0;
        $ingreso = Carbon::parse($emp->fecha_ingreso);
        $corte = Carbon::parse($hasta);
        if ($corte->lt($ingreso)) return 0.0;
        $meses = $ingreso->diffInMonths($corte);
        $diasPorAnio = (float) ($emp->dias_vacaciones_anuales ?? 15);
        return round(($diasPorAnio / 12) * $meses, 2);
    }

    /** Días ya tomados (registrados, no anulados) hasta una fecha. */
    public static function diasTomados(Empleado $emp, ?string $hasta = null): float
    {
        $q = VacacionTomada::where('empleado_id', $emp->id)->where('estado', '!=', 'anulada');
        if ($hasta) $q->where('fecha_fin', '<=', $hasta);
        return round((float) $q->sum('dias'), 2);
    }

    /** Días pendientes a una fecha de corte. */
    public static function diasPendientes(Empleado $emp, ?string $hasta = null): float
    {
        $hasta = $hasta ?: now()->toDateString();
        return round(self::diasGanados($emp, $hasta) - self::diasTomados($emp, $hasta), 2);
    }

    /** Resumen para el tablero. */
    public static function resumen(?string $hasta = null): array
    {
        $hasta = $hasta ?: now()->toDateString();
        $out = [];
        foreach (Empleado::where('activo', true)->where('relacion', 'dependencia')->orderBy('nombre')->get() as $e) {
            $ganados = self::diasGanados($e, $hasta);
            $tomados = self::diasTomados($e, $hasta);
            $out[] = [
                'empleado' => $e->nombre,
                'empleado_id' => $e->id,
                'ganados' => $ganados,
                'tomados' => $tomados,
                'pendientes' => round($ganados - $tomados, 2),
            ];
        }
        return $out;
    }
}
