<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Incentivo extends Model
{
    protected $table = 'incentivos';
    protected $fillable = [
        'folio',
        'empleado_id',
        'fecha',
        'concepto',
        'monto',
        'ret_renta',
        'total',
        'metodo_pago',
        'nro_comprobante',
        'adjunto',
        'nota',
        'estado',
        'creado_por',
    ];
    protected $casts = ['fecha' => 'date', 'monto' => 'decimal:2', 'ret_renta' => 'decimal:2', 'total' => 'decimal:2'];
    public function empleado() { return $this->belongsTo(Empleado::class); }
    protected static function booted(): void
    {
        static::creating(function (Incentivo $i) {
            if (empty($i->folio) && class_exists(\App\Services\Folios::class)) {
                try { $i->folio = \App\Services\Folios::next('INC'); } catch (\Throwable $e) {}
            }
            $i->total = round((float) $i->monto - (float) $i->ret_renta, 2);
        });
        static::updating(function (Incentivo $i) {
            $i->total = round((float) $i->monto - (float) $i->ret_renta, 2);
        });
    }
}
