<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class WooPedido extends Model {
    protected $table = 'woo_pedidos';
    public $timestamps = false;
    protected $fillable = [
        'woo_id',
        'numero',
        'estado',
        'total',
        'moneda',
        'cliente_nombre',
        'cliente_email',
        'woo_customer_id',
        'fecha',
        'raw',
        'importado_en',
    ];
    protected $casts = ['raw' => 'array', 'fecha' => 'datetime', 'importado_en' => 'datetime'];
    public function items(): HasMany { return $this->hasMany(WooPedidoItem::class, 'woo_pedido_id'); }
}
