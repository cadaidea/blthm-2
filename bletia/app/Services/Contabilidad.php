<?php

namespace App\Services;

use App\Models\Asiento;
use App\Models\AsientoLinea;
use App\Models\Cuenta;
use Illuminate\Support\Facades\DB;

/**
 * Motor contable de partida doble.
 * Reglas duras:
 *  - Un asiento solo se guarda si DEBE == HABER (cuadra). Si no, lanza excepción.
 *  - Nada se borra: se reversa con un asiento contrario (golden rule del ERP).
 */
class Contabilidad
{
    /**
     * Crea un asiento cuadrado.
     * @param array $lineas  [ ['cuenta' => 'codigo|id', 'debe' => x, 'haber' => y, 'detalle' => ''], ... ]
     */
    public static function asentar(string $fecha, string $glosa, array $lineas, array $meta = []): Asiento
    {
        $prep = [];
        $totDebe = 0.0; $totHaber = 0.0;

        foreach ($lineas as $l) {
            $cuentaId = self::resolverCuenta($l['cuenta'] ?? null);
            if (! $cuentaId) {
                throw new \RuntimeException('Cuenta contable no encontrada: ' . ($l['cuenta'] ?? '—'));
            }
            $debe  = round((float) ($l['debe'] ?? 0), 2);
            $haber = round((float) ($l['haber'] ?? 0), 2);
            if ($debe < 0 || $haber < 0) throw new \RuntimeException('Montos negativos no permitidos.');
            if ($debe > 0 && $haber > 0) throw new \RuntimeException('Una línea no puede tener debe y haber a la vez.');
            $totDebe += $debe; $totHaber += $haber;
            $prep[] = ['cuenta_id' => $cuentaId, 'debe' => $debe, 'haber' => $haber, 'detalle' => $l['detalle'] ?? null];
        }

        if (round($totDebe, 2) !== round($totHaber, 2)) {
            throw new \RuntimeException("El asiento no cuadra: debe {$totDebe} ≠ haber {$totHaber}.");
        }
        if (round($totDebe, 2) === 0.0) {
            throw new \RuntimeException('El asiento está en cero.');
        }

        return DB::transaction(function () use ($fecha, $glosa, $prep, $totDebe, $meta) {
            $asiento = Asiento::create([
                'fecha'       => $fecha,
                'glosa'       => $glosa,
                'origen'      => $meta['origen'] ?? 'manual',
                'origen_tipo' => $meta['origen_tipo'] ?? null,
                'origen_id'   => $meta['origen_id'] ?? null,
                'debe'        => $totDebe,
                'haber'       => $totDebe,
                'estado'      => 'registrado',
                'creado_por'  => auth()->id(),
            ]);
            foreach ($prep as $p) {
                $p['asiento_id'] = $asiento->id;
                $p['created_at'] = now(); $p['updated_at'] = now();
                AsientoLinea::insert($p);
            }
            return $asiento;
        });
    }

    /** Reversa un asiento (invierte debe/haber). No borra el original. */
    public static function reversar(Asiento $asiento, ?string $fecha = null): Asiento
    {
        if ($asiento->estado === 'anulado') {
            throw new \RuntimeException('El asiento ya está anulado.');
        }
        $lineas = [];
        foreach ($asiento->lineas as $l) {
            $lineas[] = [
                'cuenta'  => $l->cuenta_id,
                'debe'    => (float) $l->haber,   // invertido
                'haber'   => (float) $l->debe,
                'detalle' => 'Reverso',
            ];
        }
        $rev = self::asentar(
            $fecha ?: now()->toDateString(),
            'REVERSO de asiento ' . ($asiento->numero ?: $asiento->id) . ' · ' . $asiento->glosa,
            $lineas,
            ['origen' => 'reverso', 'origen_tipo' => 'Asiento', 'origen_id' => $asiento->id]
        );
        $asiento->update(['estado' => 'anulado', 'reversa_id' => $rev->id]);
        return $rev;
    }

    /** Evita duplicar el asiento automático de un mismo evento. */
    public static function yaExiste(string $origenTipo, int $origenId, string $origen): bool
    {
        return Asiento::where('origen_tipo', $origenTipo)
            ->where('origen_id', $origenId)
            ->where('origen', $origen)
            ->where('estado', 'registrado')
            ->exists();
    }

    protected static function resolverCuenta($ref): ?int
    {
        if (is_numeric($ref)) return (int) $ref;
        if (is_string($ref)) {
            $c = Cuenta::where('codigo', $ref)->first();
            return $c?->id;
        }
        return null;
    }

    /** Saldo de una cuenta en un rango (para mayor/balance). */
    public static function saldoCuenta(int $cuentaId, ?string $desde = null, ?string $hasta = null): array
    {
        $q = AsientoLinea::query()
            ->join('asientos', 'asientos.id', '=', 'asiento_lineas.asiento_id')
            ->where('asiento_lineas.cuenta_id', $cuentaId)
            ->where('asientos.estado', 'registrado');
        if ($desde) $q->where('asientos.fecha', '>=', $desde);
        if ($hasta) $q->where('asientos.fecha', '<=', $hasta);
        $debe  = (float) $q->clone()->sum('asiento_lineas.debe');
        $haber = (float) $q->clone()->sum('asiento_lineas.haber');
        return ['debe' => round($debe, 2), 'haber' => round($haber, 2), 'saldo' => round($debe - $haber, 2)];
    }
}
