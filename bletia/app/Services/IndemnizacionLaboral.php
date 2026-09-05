<?php

namespace App\Services;

use App\Models\Empleado;
use App\Models\RolPago;
use Illuminate\Support\Carbon;

/**
 * Indemnización por despido intempestivo (Código del Trabajo Ecuador).
 * Art. 188: <3 años servicio = 3 meses de la mejor remuneración; >=3 años = 1 mes por año
 *           (cualquier fracción de año cuenta como año completo). Base: mejor remuneración
 *           de TODA la relación laboral (Resolución 02-2025 Corte Nacional de Justicia).
 * Art. 185: bonificación por desahucio = 25% de la última remuneración por cada año COMPLETO
 *           (sin redondear fracción). Se paga junto a la indemnización en despido injustificado.
 *
 * Referencial: para un despido con conflicto real, validar con abogado laboral antes de pagar.
 */
class IndemnizacionLaboral
{
    /** Mejor remuneración mensual que tuvo el empleado en toda su relación laboral. */
    public static function mejorRemuneracion(Empleado $emp): float
    {
        $maxRol = (float) (RolPago::where('empleado_id', $emp->id)
            ->where('estado', '!=', 'anulado')
            ->max('sueldo') ?? 0);
        return max($maxRol, (float) $emp->sueldo);
    }

    /** Tiempo de servicio REAL (años, meses, días) — para el acta de finiquito, no para indemnización. */
    public static function tiempoServicio(Empleado $emp, string $fechaSalida): array
    {
        if (! $emp->fecha_ingreso) return ['anios' => 0, 'meses' => 0, 'dias' => 0, 'texto' => 'Sin fecha de ingreso registrada'];
        $ingreso = Carbon::parse($emp->fecha_ingreso);
        $salida = Carbon::parse($fechaSalida);
        $diff = $ingreso->diff($salida);
        $partes = [];
        if ($diff->y > 0) $partes[] = $diff->y . ' año' . ($diff->y === 1 ? '' : 's');
        if ($diff->m > 0) $partes[] = $diff->m . ' mes' . ($diff->m === 1 ? '' : 'es');
        if ($diff->d > 0 || empty($partes)) $partes[] = $diff->d . ' día' . ($diff->d === 1 ? '' : 's');
        return ['anios' => $diff->y, 'meses' => $diff->m, 'dias' => $diff->d, 'texto' => implode(', ', $partes)];
    }

    /** Años de servicio: completos (para bonificación) y con fracción=año completo (para indemnización). */
    public static function aniosServicio(Empleado $emp, string $fechaSalida): array
    {
        $ingreso = Carbon::parse($emp->fecha_ingreso);
        $salida = Carbon::parse($fechaSalida);
        $meses = max(0, $ingreso->diffInMonths($salida));
        $completos = intdiv($meses, 12);
        $resto = $meses % 12;
        $conFraccion = $resto > 0 ? $completos + 1 : max($completos, 0);
        // Mínimo 1 año para indemnización si hubo relación laboral (fracción de año = año completo)
        if ($conFraccion === 0 && $meses > 0) $conFraccion = 1;
        return ['completos' => $completos, 'con_fraccion' => $conFraccion];
    }

    /**
     * Calcula indemnización (Art. 188) y bonificación desahucio (Art. 185) según motivo.
     * Solo aplica si $motivo === 'despido' (despido intempestivo/injustificado).
     */
    public static function calcular(Empleado $emp, string $motivo, string $fechaSalida): array
    {
        $anios = self::aniosServicio($emp, $fechaSalida);
        $mejorRem = self::mejorRemuneracion($emp);

        $indemnizacion = 0.0;
        $bonificacion = 0.0;

        if ($motivo === 'despido') {
            $indemnizacion = $anios['con_fraccion'] < 3
                ? round($mejorRem * 3, 2)
                : round($mejorRem * $anios['con_fraccion'], 2);

            $bonificacion = round((float) $emp->sueldo * 0.25 * $anios['completos'], 2);
        }

        return [
            'indemnizacion' => $indemnizacion,
            'bonificacion_desahucio' => $bonificacion,
            'anios_servicio' => $anios['con_fraccion'],
            'mejor_remuneracion' => $mejorRem,
        ];
    }
}
