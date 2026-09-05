<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PagoBeneficio extends Model
{
    protected $table = 'pagos_beneficio';
    protected $fillable = [
        'folio',
        'empleado_id',
        'tipo',
        'periodo',
        'fecha',
        'monto',
        'metodo_pago',
        'nro_comprobante',
        'adjunto',
        'detalle',
        'estado',
        'creado_por',
    ];
    protected $casts = ['fecha' => 'date', 'monto' => 'decimal:2'];
    public function empleado() { return $this->belongsTo(Empleado::class); }
    protected static function booted(): void
    {
        static::creating(function (PagoBeneficio $p) {
            if (empty($p->folio) && class_exists(\App\Services\Folios::class)) {
                try { $p->folio = \App\Services\Folios::next('BEN'); } catch (\Throwable $e) {}
            }
        });
    }
    public const TIPOS = [
        'decimo_tercero' => 'Décimo tercero',
        'decimo_cuarto' => 'Décimo cuarto',
        'fondos_reserva' => 'Fondos de reserva',
        'vacaciones' => 'Vacaciones',
    ];
}
