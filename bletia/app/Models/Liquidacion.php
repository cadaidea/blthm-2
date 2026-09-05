<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Liquidacion extends Model
{
    protected $table = 'liquidaciones';
    protected $fillable = [
        'folio',
        'empleado_id',
        'fecha',
        'fecha_salida',
        'motivo',
        'decimo_tercero',
        'decimo_cuarto',
        'vacaciones',
        'fondos_reserva',
        'indemnizacion',
        'bonificacion_desahucio',
        'anios_servicio',
        'mejor_remuneracion',
        'tiempo_servicio',
        'otros',
        'descuentos',
        'total',
        'detalle',
        'adjunto',
        'estado',
        'creado_por',
    ];
    protected $casts = [
        'fecha' => 'date', 'fecha_salida' => 'date',
        'decimo_tercero' => 'decimal:2', 'decimo_cuarto' => 'decimal:2', 'vacaciones' => 'decimal:2',
        'fondos_reserva' => 'decimal:2', 'otros' => 'decimal:2', 'descuentos' => 'decimal:2', 'total' => 'decimal:2',
    ];
    public function empleado() { return $this->belongsTo(Empleado::class); }
    protected static function booted(): void
    {
        static::creating(function (Liquidacion $l) {
            if (empty($l->folio) && class_exists(\App\Services\Folios::class)) {
                try { $l->folio = \App\Services\Folios::next('LIQ'); } catch (\Throwable $e) {}
            }
        });
    }
}
