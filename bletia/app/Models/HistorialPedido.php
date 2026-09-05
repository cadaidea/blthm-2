<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class HistorialPedido extends Model {
    protected $table = 'historial_pedido';
    public $timestamps = false;
    protected $fillable = ['pedido_id', 'estado_anterior', 'estado_nuevo', 'usuario_id', 'notas', 'creado_en'];
    protected $casts = ['creado_en' => 'datetime'];
}
