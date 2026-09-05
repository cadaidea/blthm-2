<?php

namespace App\Services;

use App\Models\Empleado;
use App\Models\PagoBeneficio;
use App\Models\RolPago;
use Illuminate\Support\Carbon;

/**
 * Acumulados de beneficios por empleado.
 * Provisionado = suma de lo generado en roles (pagados o no) - lo ya pagado como beneficio.
 * Los décimos en modo "acumulado" son los que se acumulan aquí; los mensualizados ya se pagaron en el rol.
 */
class AcumuladosBeneficios
{
    /**
     * Acumulado de un empleado por tipo de beneficio, opcionalmente en un rango.
     * Décimo tercero: periodo dic-nov. Décimo cuarto: por región. Fondos: anual.
     */
    public static function deEmpleado(Empleado $emp, ?string $desde = null, ?string $hasta = null): array
    {
        $q = RolPago::where('empleado_id', $emp->id)->where('estado', '!=', 'anulado');
        if ($desde) $q->whereRaw("STR_TO_DATE(CONCAT(anio,'-',mes,'-01'),'%Y-%m-%d') >= ?", [$desde]);
        if ($hasta) $q->whereRaw("STR_TO_DATE(CONCAT(anio,'-',mes,'-01'),'%Y-%m-%d') <= ?", [$hasta]);
        $roles = $q->get();

        // Solo acumula lo que NO se pagó mensualizado (modo acumulado).
        $prov13 = $emp->modo_decimo_tercero === 'acumulado' ? round($roles->sum('decimo_tercero'), 2) : 0.0;
        $prov14 = $emp->modo_decimo_cuarto === 'acumulado' ? round($roles->sum('decimo_cuarto'), 2) : 0.0;
        $provFR = $emp->modo_fondos_reserva === 'acumulado' ? round($roles->sum('fondos_reserva'), 2) : 0.0;
        $provVac = round($roles->sum('vacaciones'), 2); // vacaciones siempre se acumulan hasta que se gozan

        // Restar lo ya pagado
        $pagos = PagoBeneficio::where('empleado_id', $emp->id)->where('estado', 'pagado')->get();
        $pag13 = round($pagos->where('tipo', 'decimo_tercero')->sum('monto'), 2);
        $pag14 = round($pagos->where('tipo', 'decimo_cuarto')->sum('monto'), 2);
        $pagFR = round($pagos->where('tipo', 'fondos_reserva')->sum('monto'), 2);
        $pagVac = round($pagos->where('tipo', 'vacaciones')->sum('monto'), 2);

        $detalle = [
            'decimo_tercero' => ['modo' => $emp->modo_decimo_tercero, 'bruto' => $prov13, 'pagado' => $pag13, 'pendiente' => round($prov13 - $pag13, 2)],
            'decimo_cuarto'  => ['modo' => $emp->modo_decimo_cuarto, 'bruto' => $prov14, 'pagado' => $pag14, 'pendiente' => round($prov14 - $pag14, 2)],
            'fondos_reserva' => ['modo' => $emp->modo_fondos_reserva, 'bruto' => $provFR, 'pagado' => $pagFR, 'pendiente' => round($provFR - $pagFR, 2)],
            'vacaciones'     => ['modo' => 'acumulado', 'bruto' => $provVac, 'pagado' => $pagVac, 'pendiente' => round($provVac - $pagVac, 2)],
        ];

        return [
            'decimo_tercero' => $detalle['decimo_tercero']['pendiente'],
            'decimo_cuarto'  => $detalle['decimo_cuarto']['pendiente'],
            'fondos_reserva' => $detalle['fondos_reserva']['pendiente'],
            'vacaciones'     => $detalle['vacaciones']['pendiente'],
            'n_roles'        => $roles->count(),
            'detalle'        => $detalle,
        ];
    }

    /** Resumen de todos los empleados activos. */
    public static function todos(?string $desde = null, ?string $hasta = null): array
    {
        $out = [];
        foreach (Empleado::where('activo', true)->where('relacion', 'dependencia')->orderBy('nombre')->get() as $e) {
            $ac = self::deEmpleado($e, $desde, $hasta);
            $ac['empleado'] = $e->nombre;
            $ac['empleado_id'] = $e->id;
            $ac['total'] = round($ac['decimo_tercero'] + $ac['decimo_cuarto'] + $ac['fondos_reserva'] + $ac['vacaciones'], 2);
            $out[] = $ac;
        }
        return $out;
    }

    /** Calcula la liquidación de haberes de un empleado a su fecha de salida. */
    public static function liquidacion(Empleado $emp, string $fechaSalida): array
    {
        $ac = self::deEmpleado($emp);
        // A lo acumulado se suma la parte proporcional del periodo en curso ya está en los roles.
        return [
            'decimo_tercero' => $ac['decimo_tercero'],
            'decimo_cuarto'  => $ac['decimo_cuarto'],
            'fondos_reserva' => $ac['fondos_reserva'],
            'vacaciones'     => $ac['vacaciones'],
            'total'          => round($ac['decimo_tercero'] + $ac['decimo_cuarto'] + $ac['fondos_reserva'] + $ac['vacaciones'], 2),
            'detalle'        => $ac['detalle'] ?? [],
            'n_roles'        => $ac['n_roles'] ?? 0,
        ];
    }
}
