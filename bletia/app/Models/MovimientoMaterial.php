<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoMaterial extends Model
{
    protected $table = 'movimientos_material';
    protected $fillable = [
        'materia_prima_id',
        'pedido_id',
        'compra_id',
        'tipo',
        'cantidad',
        'estado',
        'nota',
        'user_id',
        'recibido_nombre',
        'recibido_cedula',
        'firma',
        'pdf_entrega',
        'entregado_at',
    ];
    protected $casts = ['cantidad' => 'decimal:2'];

    public function materia(): BelongsTo { return $this->belongsTo(MateriaPrima::class, 'materia_prima_id'); }

    protected static function booted(): void
    {
        // al ENTREGAR o USAR, descuenta del stock; ENTRADA suma
        static::created(function (self $m) { $m->aplicar(); });
    }

    public function aplicar(): void
    {
        $mp = $this->materia;
        if (! $mp) return;
        $c = (float) $this->cantidad;
        if (in_array($this->tipo, ['uso', 'entrega'], true) && $this->estado !== 'solicitado') {
            $mp->stock = max(0, (float) $mp->stock - $c);
            $mp->save();
        } elseif (in_array($this->tipo, ['entrada', 'ajuste', 'devolucion'], true)) {
            $mp->stock = (float) $mp->stock + $c;
            $mp->save();
        }
        // 'solicitud' (estado solicitado) NO descuenta hasta que se entregue
    }
}
