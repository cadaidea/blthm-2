<?php

namespace App\Support;

use App\Models\Impuesto;
use Illuminate\Support\Facades\Cache;

class Impuestos
{
    /**
     * % de IVA vigente en una fecha (por defecto hoy).
     * Devuelve float, ej 15.0. Fallback seguro a 15 si no hay tabla/dato
     * (así nunca rompe facturación aunque falte el seed).
     */
    public static function ivaVigente(?string $fecha = null): float
    {
        $fecha = $fecha ?: now()->toDateString();
        $key = 'iva_vigente_' . $fecha;

        return (float) Cache::remember($key, 3600, function () use ($fecha) {
            try {
                $row = Impuesto::where('tipo', 'iva')->where('activo', true)
                    ->where('vigente_desde', '<=', $fecha)
                    ->where(function ($q) use ($fecha) {
                        $q->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', $fecha);
                    })
                    ->orderByDesc('vigente_desde')->first();
                return $row ? (float) $row->porcentaje : 15.0;
            } catch (\Throwable $e) {
                return 15.0;
            }
        });
    }

    /** Divisor para separar IVA de un precio que ya lo incluye. Ej: 1.15 */
    public static function divisorIva(?string $fecha = null): float
    {
        return 1 + (self::ivaVigente($fecha) / 100);
    }

    /** Limpia la caché (llamar al guardar un impuesto). */
    public static function olvidar(): void
    {
        Cache::flush();
    }
}
