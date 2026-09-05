<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class VacacionTomada extends Model
{
    protected $table = 'vacaciones_tomadas';
    protected $fillable = [
        'folio',
        'empleado_id',
        'fecha_inicio',
        'fecha_fin',
        'dias',
        'nota',
        'adjunto',
        'estado',
        'creado_por',
    ];
    protected $casts = ['fecha_inicio' => 'date', 'fecha_fin' => 'date', 'dias' => 'decimal:2'];
    public function empleado() { return $this->belongsTo(Empleado::class); }
    protected static function booted(): void
    {
        static::creating(function (VacacionTomada $v) {
            if (empty($v->folio) && class_exists(\App\Services\Folios::class)) {
                try { $v->folio = \App\Services\Folios::next('VAC'); } catch (\Throwable $e) {}
            }
        });
    }
}
