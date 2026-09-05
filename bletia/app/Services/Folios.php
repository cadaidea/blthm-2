<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Generador único de correlativos internos del ERP.
 * Motor atómico (lockForUpdate sobre tabla `secuencias`): sin huecos, sin colisiones,
 * seguro ante concurrencia. Fuente de verdad para TODOS los códigos internos.
 *
 * Nota fiscal: las FACTURAS no se numeran aquí. Usan el secuencial fiscal del SRI
 * (App\Services\Sri\Secuencial, formato 001-001-000000001), que es el mismo número
 * dentro del sistema y ante el SRI. Aquí viven solo los correlativos internos (PED, VEN, etc.).
 */
class Folios
{
    /** Prefijos por tipo de documento. */
    public const PREFIJOS = [
        'PED' => 'PED-',  // Pedido (canal único de pedidos a fabricar)
        'VEN' => 'VEN-',  // Nota de venta (venta de stock, interna, no SRI)
        'OF'  => 'OF-',   // Orden de fabricación (proveedor)
        'REC' => 'REC-',  // Recibo de pago/anticipo
        'ANL' => 'ANL-',  // Anulación
        'GUI' => 'GUI-',  // Guía / despacho
        'DES' => 'DES-',  // Despacho
        'VL'  => 'VL-',   // (legado) Venta local — conservado solo por compatibilidad histórica
    ];

    /** Devuelve el siguiente folio del tipo (ej. PED-000018). Correlativo continuo por tipo, desde 1. */
    public static function next(string $tipo): string
    {
        $tipo = strtoupper($tipo);
        $prefijo = self::PREFIJOS[$tipo] ?? ($tipo . '-');

        if (! Schema::hasTable('secuencias')) {
            return $prefijo . str_pad((string) time(), 6, '0', STR_PAD_LEFT);
        }

        return DB::transaction(function () use ($tipo, $prefijo) {
            $row = DB::table('secuencias')->where('tipo', $tipo)->lockForUpdate()->first();
            if (! $row) {
                DB::table('secuencias')->insert(['tipo' => $tipo, 'ultimo' => 0, 'created_at' => now(), 'updated_at' => now()]);
                $ultimo = 0;
            } else {
                $ultimo = (int) $row->ultimo;
            }
            $nuevo = $ultimo + 1;
            DB::table('secuencias')->where('tipo', $tipo)->update(['ultimo' => $nuevo, 'updated_at' => now()]);
            return $prefijo . str_pad((string) $nuevo, 6, '0', STR_PAD_LEFT);
        });
    }

    /** Lee el último número usado de un tipo sin incrementar (para diagnósticos/UI). */
    public static function ultimo(string $tipo): int
    {
        $tipo = strtoupper($tipo);
        if (! Schema::hasTable('secuencias')) return 0;
        return (int) (DB::table('secuencias')->where('tipo', $tipo)->value('ultimo') ?? 0);
    }

    /** Fija el contador de un tipo a un valor dado (usado en migraciones de unificación). */
    public static function fijar(string $tipo, int $valor): void
    {
        $tipo = strtoupper($tipo);
        if (! Schema::hasTable('secuencias')) return;
        $existe = DB::table('secuencias')->where('tipo', $tipo)->exists();
        if ($existe) {
            DB::table('secuencias')->where('tipo', $tipo)->update(['ultimo' => $valor, 'updated_at' => now()]);
        } else {
            DB::table('secuencias')->insert(['tipo' => $tipo, 'ultimo' => $valor, 'created_at' => now(), 'updated_at' => now()]);
        }
    }
}
