<?php

namespace App\Services\Sri;

use Illuminate\Support\Facades\DB;

class Secuencial
{
    /** Devuelve y reserva el siguiente secuencial (9 dígitos) para el tipo de doc. Atómico. */
    public static function siguiente(string $codDoc, string $estab = '001', string $ptoEmi = '001'): string
    {
        return DB::transaction(function () use ($codDoc, $estab, $ptoEmi) {
            $row = DB::table('sri_secuenciales')
                ->where('cod_doc', $codDoc)->where('estab', $estab)->where('pto_emi', $ptoEmi)
                ->lockForUpdate()->first();
            if (! $row) {
                $ultimo = 1;
                DB::table('sri_secuenciales')->insert([
                    'cod_doc' => $codDoc, 'estab' => $estab, 'pto_emi' => $ptoEmi,
                    'ultimo' => $ultimo, 'created_at' => now(), 'updated_at' => now(),
                ]);
            } else {
                $ultimo = (int) $row->ultimo + 1;
                DB::table('sri_secuenciales')->where('id', $row->id)->update(['ultimo' => $ultimo, 'updated_at' => now()]);
            }
            return str_pad((string) $ultimo, 9, '0', STR_PAD_LEFT);
        });
    }

    /** Fija el secuencial inicial (para arrancar desde el número que ya usa el negocio). */
    public static function fijar(string $codDoc, int $desde, string $estab = '001', string $ptoEmi = '001'): void
    {
        DB::table('sri_secuenciales')->updateOrInsert(
            ['cod_doc' => $codDoc, 'estab' => $estab, 'pto_emi' => $ptoEmi],
            ['ultimo' => max(0, $desde - 1), 'updated_at' => now(), 'created_at' => now()]
        );
    }
}
