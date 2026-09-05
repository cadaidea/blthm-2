<?php

namespace App\Services;

use App\Models\Empleado;
use App\Models\ParametroLaboral;
use App\Models\RolPago;

/**
 * Cálculo del rol de pagos según normativa ecuatoriana.
 * Parámetros (SBU, %IESS, fondos) desde parametros_laborales por año (editable).
 */
class Nomina
{
    public static function parametros(int $anio): ParametroLaboral
    {
        return ParametroLaboral::firstOrCreate(
            ['anio' => $anio],
            ['sbu' => 482.00, 'aporte_personal' => 9.45, 'aporte_patronal' => 11.15, 'fondos_reserva' => 8.33]
        );
    }

    /**
     * Calcula (sin guardar) los valores del rol a partir de los ingresos capturados.
     * $in = ['sueldo','horas_extra','comisiones','bonos','otros_ingresos',
     *        'anticipos','prestamos_iess','otros_descuentos','ret_renta']
     */
    public static function calcular(Empleado $emp, int $anio, int $mes, array $in): array
    {
        $p = self::parametros($anio);
        $dependencia = $emp->relacion === 'dependencia';

        $sueldo   = (float) ($in['sueldo'] ?? $emp->sueldo);

        // Horas extra automáticas (Código de Trabajo EC): valor hora = sueldo / 240.
        // Suplementaria recargo 50% (x1.5), extraordinaria recargo 100% (x2).
        $valorHora = $sueldo > 0 ? $sueldo / 240 : 0;
        $horasSupl = (float) ($in['horas_suplementarias'] ?? 0);
        $horasExt  = (float) ($in['horas_extraordinarias'] ?? 0);
        $extra = round($horasSupl * $valorHora * 1.5 + $horasExt * $valorHora * 2, 2);
        $comis    = (float) ($in['comisiones'] ?? 0);
        $bonos    = (float) ($in['bonos'] ?? 0);
        $otrosIng = (float) ($in['otros_ingresos'] ?? 0);

        $totalIngresos = round($sueldo + $extra + $comis + $bonos + $otrosIng, 2);

        // Base para IESS: remuneración (sueldo + extras + comisiones + bonos). No incluye "otros".
        $baseIess = round($sueldo + $extra + $comis + $bonos, 2);

        // ----- DESCUENTOS -----
        $aportePersonal = $dependencia ? round($baseIess * ((float)$p->aporte_personal) / 100, 2) : 0.0;
        $anticipos      = (float) ($in['anticipos'] ?? 0);
        $prestamos      = (float) ($in['prestamos_iess'] ?? 0);
        $otrosDesc      = (float) ($in['otros_descuentos'] ?? 0);
        $retRenta       = (float) ($in['ret_renta'] ?? 0);

        $totalDescuentos = round($aportePersonal + $anticipos + $prestamos + $otrosDesc + $retRenta, 2);

        // ----- PROVISIONES PATRONALES (no se descuentan al empleado) -----
        $aportePatronal = $dependencia ? round($baseIess * ((float)$p->aporte_patronal) / 100, 2) : 0.0;

        // Provisiones proporcionales. Décimos desde el primer día (proporcional al mes trabajado).
        $decimoTercero  = $dependencia ? round($totalIngresos / 12, 2) : 0.0;   // 1/12 de lo ganado
        $decimoCuarto   = $dependencia ? round(((float)$p->sbu) / 12, 2) : 0.0; // SBU/12 proporcional
        $vacaciones     = $dependencia ? round($totalIngresos / 24, 2) : 0.0;   // 1/24

        // Fondos de reserva: SOLO tras cumplir 1 año completo (art. 196 Código de Trabajo).
        $aplica_fondos = false;
        if ($dependencia && $emp->fecha_ingreso) {
            $aplica_fondos = \Illuminate\Support\Carbon::parse($emp->fecha_ingreso)
                ->addYear()->lte(\Illuminate\Support\Carbon::create($anio, $mes, 1)->endOfMonth());
        }
        $fondosReserva = $aplica_fondos ? round($baseIess * ((float)$p->fondos_reserva) / 100, 2) : 0.0;

        // Modo de cada beneficio: mensualizado (suma al líquido) vs acumulado (solo se provisiona).
        $modo13 = $emp->modo_decimo_tercero ?? 'acumulado';
        $modo14 = $emp->modo_decimo_cuarto ?? 'acumulado';
        $modoFR = $emp->modo_fondos_reserva ?? 'mensualizado';
        $sumaLiquido = 0.0;
        if ($modo13 === 'mensualizado') $sumaLiquido += $decimoTercero;
        if ($modo14 === 'mensualizado') $sumaLiquido += $decimoCuarto;
        if ($modoFR === 'mensualizado') $sumaLiquido += $fondosReserva;
        $sumaLiquido = round($sumaLiquido, 2);

        // ----- NETO -----
        $liquido = round($totalIngresos - $totalDescuentos + $sumaLiquido, 2);
        $costoEmpresa = round($totalIngresos + $aportePatronal + $decimoTercero + $decimoCuarto + $vacaciones + $fondosReserva, 2);

        return compact(
            'sueldo', 'extra', 'comis', 'bonos', 'otrosIng', 'totalIngresos',
            'aportePersonal', 'anticipos', 'prestamos', 'otrosDesc', 'retRenta', 'totalDescuentos',
            'aportePatronal', 'decimoTercero', 'decimoCuarto', 'vacaciones', 'fondosReserva',
            'sumaLiquido', 'aplica_fondos',
            'liquido', 'costoEmpresa'
        );
    }

    /** Aplica el cálculo a un RolPago (asigna campos, no guarda). */
    public static function aplicar(RolPago $rol): void
    {
        $emp = $rol->empleado;
        $c = self::calcular($emp, $rol->anio, $rol->mes, [
            'sueldo' => $rol->sueldo ?: $emp->sueldo,
            'horas_suplementarias' => $rol->horas_suplementarias, 'horas_extraordinarias' => $rol->horas_extraordinarias,
            'horas_extra' => $rol->horas_extra, 'comisiones' => $rol->comisiones,
            'bonos' => $rol->bonos, 'otros_ingresos' => $rol->otros_ingresos,
            'anticipos' => $rol->anticipos, 'prestamos_iess' => $rol->prestamos_iess,
            'otros_descuentos' => $rol->otros_descuentos, 'ret_renta' => $rol->ret_renta,
        ]);
        $rol->sueldo = $c['sueldo'];
        $rol->horas_extra = $c['extra'];
        $rol->relacion = $emp->relacion;
        $rol->total_ingresos = $c['totalIngresos'];
        $rol->aporte_personal = $c['aportePersonal'];
        $rol->total_descuentos = $c['totalDescuentos'];
        $rol->aporte_patronal = $c['aportePatronal'];
        $rol->decimo_tercero = $c['decimoTercero'];
        $rol->decimo_cuarto = $c['decimoCuarto'];
        $rol->vacaciones = $c['vacaciones'];
        $rol->fondos_reserva = $c['fondosReserva'];
        $rol->liquido = $c['liquido'];
        $rol->costo_empresa = $c['costoEmpresa'];
    }

    /** Genera el asiento contable del rol (al marcarlo pagado). Idempotente. */
    public static function asentar(RolPago $rol): void
    {
        try {
            if (Contabilidad::yaExiste('RolPago', $rol->id, 'nomina')) return;
            $fecha = $rol->fecha_pago ? (string) $rol->fecha_pago : now()->toDateString();
            $glosa = 'Rol de pagos ' . $rol->nombreMes() . ' ' . $rol->anio . ' · ' . ($rol->empleado->nombre ?? '');

            $A = fn ($k) => ContabilidadAuto::cuenta($k);

            if ($rol->relacion === 'honorarios') {
                // Honorarios: Debe gasto honorarios / Haber líquido + retención renta
                $lineas = [
                    ['cuenta' => $A('nomina.honorarios_gasto'), 'debe' => (float) $rol->total_ingresos, 'detalle' => 'Honorarios'],
                ];
                if ((float) $rol->ret_renta > 0) $lineas[] = ['cuenta' => $A('nomina.ret_renta_pagar'), 'haber' => (float) $rol->ret_renta, 'detalle' => 'Ret. renta'];
                $lineas[] = ['cuenta' => $A('nomina.liquido_pagar'), 'haber' => (float) $rol->liquido, 'detalle' => 'Por pagar'];
            } else {
                // Dependencia:
                // Debe: sueldo (total ingresos) + aporte patronal + beneficios (13/14/vac/fondos)
                // Haber: líquido + IESS (personal+patronal) + beneficios por pagar + ret renta
                $beneficios = round((float)$rol->decimo_tercero + (float)$rol->decimo_cuarto + (float)$rol->vacaciones + (float)$rol->fondos_reserva, 2);
                $iessTotal = round((float)$rol->aporte_personal + (float)$rol->aporte_patronal, 2);

                $lineas = [
                    ['cuenta' => $A('nomina.sueldo_gasto'), 'debe' => (float) $rol->total_ingresos, 'detalle' => 'Sueldo'],
                    ['cuenta' => $A('nomina.aporte_patronal_gasto'), 'debe' => (float) $rol->aporte_patronal, 'detalle' => 'Aporte patronal'],
                ];
                if ($beneficios > 0) $lineas[] = ['cuenta' => $A('nomina.beneficios_gasto'), 'debe' => $beneficios, 'detalle' => 'Beneficios sociales'];

                // Descuentos que NO son IESS ni renta (anticipos, préstamos IESS, otros): se descuentan del líquido.
                // Contablemente reducen la obligación de pago; para cuadrar el asiento van como haber a IESS por pagar
                // (préstamos IESS) o a cuentas por cobrar empleados (anticipos). Aquí se agrupan en beneficios/otros por pagar.
                $otrosDesc = round((float)$rol->total_descuentos - (float)$rol->aporte_personal - (float)$rol->ret_renta, 2);

                $lineas[] = ['cuenta' => $A('nomina.liquido_pagar'), 'haber' => (float) $rol->liquido, 'detalle' => 'Líquido a pagar'];
                if ($iessTotal > 0) $lineas[] = ['cuenta' => $A('nomina.iess_pagar'), 'haber' => $iessTotal, 'detalle' => 'IESS por pagar'];
                if ($beneficios > 0) $lineas[] = ['cuenta' => $A('nomina.beneficios_pagar'), 'haber' => $beneficios, 'detalle' => 'Beneficios por pagar'];
                if ((float) $rol->ret_renta > 0) $lineas[] = ['cuenta' => $A('nomina.ret_renta_pagar'), 'haber' => (float) $rol->ret_renta, 'detalle' => 'Ret. renta'];
                if ($otrosDesc > 0) $lineas[] = ['cuenta' => $A('nomina.beneficios_pagar'), 'haber' => $otrosDesc, 'detalle' => 'Anticipos/préstamos retenidos'];
            }

            $lineas = array_values(array_filter($lineas, fn ($l) => ($l['debe'] ?? 0) > 0 || ($l['haber'] ?? 0) > 0));

            Contabilidad::asentar($fecha, $glosa, $lineas, ['origen' => 'nomina', 'origen_tipo' => 'RolPago', 'origen_id' => $rol->id]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Nómina asiento rol#' . $rol->id . ': ' . $e->getMessage());
        }
    }
}
