<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoHistorial extends Model
{
    protected $table = 'pedido_historial';
    public $timestamps = false;
    protected $fillable = [
        'pedido_id',
        'accion',
        'user_id',
        'user_nombre',
        'rol',
        'nota',
    ];
    protected $casts = ['created_at' => 'datetime'];

    public function pedido(): BelongsTo { return $this->belongsTo(Pedido::class, 'pedido_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }

    public const ETIQUETAS = [
        'vendido'             => 'Vendido',
        'enviado_aprobacion'  => 'Enviado a aprobación',
        'aprobado'            => 'Aprobado',
        'enviado_fabricacion' => 'Enviado a fabricación',
        'despachado'          => 'Despachado',
        'recibido'            => 'Recibido',
        'pago_validado'       => 'Pago validado',
        'anulado'             => 'Anulado',
    ];
}
