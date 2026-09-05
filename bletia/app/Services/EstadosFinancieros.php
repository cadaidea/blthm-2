<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Estados financieros derivados de los asientos registrados.
 * Estado de resultados: ingresos - costos - gastos = utilidad del periodo.
 * Balance general: activo = pasivo + patrimonio + utilidad, a fecha de corte.
 * Solo lectura.
 */
class EstadosFinancieros
{
    /** Saldos por cuenta de movimiento en un rango (o hasta una fecha). */
    protected static function saldos(?string $desde, string $hasta): array
    {
        $q = DB::table('asiento_lineas as al')
            ->join('asientos as a', 'a.id', '=', 'al.asiento_id')
            ->join('cuentas as c', 'c.id', '=', 'al.cuenta_id')
            ->where('a.estado', 'registrado')
            ->where('a.fecha', '<=', $hasta);
        if ($desde) $q->where('a.fecha', '>=', $desde);

        return $q->groupBy('c.id', 'c.codigo', 'c.nombre', 'c.tipo', 'c.naturaleza')
            ->select('c.codigo', 'c.nombre', 'c.tipo', 'c.naturaleza',
                DB::raw('SUM(al.debe) as debe'), DB::raw('SUM(al.haber) as haber'))
            ->orderBy('c.codigo')->get()
            ->map(function ($r) {
                // saldo natural: deudora = debe - haber; acreedora = haber - debe
                $saldo = $r->naturaleza === 'deudora'
                    ? (float) $r->debe - (float) $r->haber
                    : (float) $r->haber - (float) $r->debe;
                return (object) ['codigo' => $r->codigo, 'nombre' => $r->nombre, 'tipo' => $r->tipo, 'saldo' => round($saldo, 2)];
            })->all();
    }

    /** Estado de resultados del periodo [desde, hasta]. */
    public static function resultados(string $desde, string $hasta): array
    {
        $filas = self::saldos($desde, $hasta);
        $ingresos = []; $costos = []; $gastos = [];
        $tIng = 0; $tCos = 0; $tGas = 0;

        foreach ($filas as $f) {
            if ($f->saldo == 0) continue;
            if ($f->tipo === 'ingreso') { $ingresos[] = $f; $tIng += $f->saldo; }
            elseif ($f->tipo === 'costo') { $costos[] = $f; $tCos += $f->saldo; }
            elseif ($f->tipo === 'gasto') { $gastos[] = $f; $tGas += $f->saldo; }
        }

        $utilidadBruta = round($tIng - $tCos, 2);
        $utilidadNeta  = round($tIng - $tCos - $tGas, 2);

        return [
            'desde' => $desde, 'hasta' => $hasta,
            'ingresos' => $ingresos, 'costos' => $costos, 'gastos' => $gastos,
            'total_ingresos' => round($tIng, 2), 'total_costos' => round($tCos, 2), 'total_gastos' => round($tGas, 2),
            'utilidad_bruta' => $utilidadBruta, 'utilidad_neta' => $utilidadNeta,
        ];
    }

    /** Balance general a fecha de corte. */
    public static function balance(string $hasta): array
    {
        $filas = self::saldos(null, $hasta);
        $activo = []; $pasivo = []; $patrimonio = [];
        $tA = 0; $tP = 0; $tPat = 0;
        $tIng = 0; $tCos = 0; $tGas = 0;

        foreach ($filas as $f) {
            if ($f->saldo == 0) continue;
            switch ($f->tipo) {
                case 'activo':     $activo[] = $f; $tA += $f->saldo; break;
                case 'pasivo':     $pasivo[] = $f; $tP += $f->saldo; break;
                case 'patrimonio': $patrimonio[] = $f; $tPat += $f->saldo; break;
                case 'ingreso':    $tIng += $f->saldo; break;
                case 'costo':      $tCos += $f->saldo; break;
                case 'gasto':      $tGas += $f->saldo; break;
            }
        }

        // La utilidad del ejercicio (ingresos - costos - gastos) es parte del patrimonio.
        $utilidad = round($tIng - $tCos - $tGas, 2);
        $totalPatrimonio = round($tPat + $utilidad, 2);
        $totalPasivoPatrimonio = round($tP + $totalPatrimonio, 2);

        return [
            'hasta' => $hasta,
            'activo' => $activo, 'pasivo' => $pasivo, 'patrimonio' => $patrimonio,
            'total_activo' => round($tA, 2),
            'total_pasivo' => round($tP, 2),
            'utilidad_ejercicio' => $utilidad,
            'total_patrimonio' => $totalPatrimonio,
            'total_pasivo_patrimonio' => $totalPasivoPatrimonio,
            'cuadra' => round($tA - $totalPasivoPatrimonio, 2) === 0.0,
            'descuadre' => round($tA - $totalPasivoPatrimonio, 2),
        ];
    }
}
