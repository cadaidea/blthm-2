<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bitacora extends Model
{
    protected $table = 'bitacora';
    public $timestamps = false;
    protected $fillable = [
        'user_id',
        'user_nombre',
        'rol',
        'evento',
        'modulo',
        'registro_id',
        'descripcion',
        'ip',
    ];
    protected $casts = ['created_at' => 'datetime'];

    /** Registra una entrada de bitácora de forma segura (nunca rompe el flujo). */
    public static function registrar(string $evento, ?string $modulo = null, $registroId = null, ?string $descripcion = null): void
    {
        try {
            $u = auth()->user();
            static::create([
                'user_id'     => $u?->id,
                'user_nombre' => $u?->name,
                'rol'         => $u?->rol,
                'evento'      => $evento,
                'modulo'      => $modulo,
                'registro_id' => is_numeric($registroId) ? (int) $registroId : null,
                'descripcion' => $descripcion ? mb_substr($descripcion, 0, 190) : null,
                'ip'          => request()->ip(),
                'created_at'  => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
