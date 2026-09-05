<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoItem extends Model
{
    protected $table = 'pedido_items';
    protected $fillable = ['pedido_id', 'producto_id', 'nombre', 'variantes', 'precio', 'iva_rate', 'cantidad', 'subtotal'];
    protected $casts = ['precio' => 'decimal:2', 'iva_rate' => 'decimal:2', 'subtotal' => 'decimal:2'];

    public function pedido(): BelongsTo { return $this->belongsTo(Pedido::class); }
    public function producto(): BelongsTo { return $this->belongsTo(Producto::class); }
}
