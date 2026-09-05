<?php

namespace App\Services;

use App\Models\Empleado;
use Illuminate\Support\Carbon;

class AlertasNomina
{
    /** Devuelve lista de alertas activas de nómina para hoy. */
    public static function alertas(): array
    {
        $hoy = Carbon::now();
        $mes = (int) $hoy->month;
        $alertas = [];

        $empleados = Empleado::where('activo', true)->where('relacion', 'dependencia')->get();

        // 1) Cumplen 1 año este mes → activan fondos de reserva
        foreach ($empleados as $e) {
            if (! $e->fecha_ingreso) continue;
            $ing = Carbon::parse($e->fecha_ingreso);
            $unAnio = $ing->copy()->addYear();
            if ($unAnio->month === $mes && $unAnio->year === (int) $hoy->year) {
                $alertas[] = [
                    'tipo' => 'fondos',
                    'nivel' => 'info',
                    'texto' => $e->nombre . ' cumple 1 año este mes (' . $unAnio->format('d/m/Y') . '). Desde ahora aplica fondos de reserva (8,33%).',
                ];
            }
        }

        // 2) Diciembre → pagar décimo tercero acumulado
        if ($mes === 12) {
            $n = $empleados->where('modo_decimo_tercero', 'acumulado')->count();
            if ($n > 0) $alertas[] = ['tipo' => 'decimo13', 'nivel' => 'warning', 'texto' => "Es diciembre: toca pagar el décimo tercero acumulado a {$n} empleado(s) hasta el 24."];
        }

        // 3) Marzo → décimo cuarto Sierra/Oriente
        if ($mes === 3) {
            $n = $empleados->where('modo_decimo_cuarto', 'acumulado')->where('region', 'sierra_oriente')->count();
            if ($n > 0) $alertas[] = ['tipo' => 'decimo14', 'nivel' => 'warning', 'texto' => "Es marzo: toca pagar el décimo cuarto a {$n} empleado(s) de Sierra/Oriente."];
        }

        // 4) Agosto → décimo cuarto Costa/Galápagos
        if ($mes === 8) {
            $n = $empleados->where('modo_decimo_cuarto', 'acumulado')->where('region', 'costa_galapagos')->count();
            if ($n > 0) $alertas[] = ['tipo' => 'decimo14', 'nivel' => 'warning', 'texto' => "Es agosto: toca pagar el décimo cuarto a {$n} empleado(s) de Costa/Galápagos."];
        }

        return $alertas;
    }
}
