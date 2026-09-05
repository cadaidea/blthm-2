<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WooPedidoItem extends Model {
    protected $table = 'woo_pedido_items';
    public $timestamps = false;
    protected $fillable = [
        'woo_pedido_id',
        'producto_nombre',
        'sku',
        'cantidad',
        'precio',
        'total',
        'variaciones',
    ];
}
