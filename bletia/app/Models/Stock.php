<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stock por ubicación. Puede ser:
 *  - General del producto (variante_id = null): para productos SIN combinaciones.
 *  - De una combinación específica (variante_id presente): producto_id se autocompleta
 *    desde la variante para que Producto->stock() (y todo lo que depende de él:
 *    stock_total, bajo_pedido, comprable) siga funcionando igual, sin tocar nada
 *    de lo que ya usa stock en el resto del ERP.
 *
 *  RECORDATORIO DE ARQUITECTURA: stock (venta directa de lo disponible) y
 *  pedidos (fabricación bajo pedido) son dos vías SEPARADAS. Esta tabla
 *  nunca se relaciona con `pedidos`.
 */
class Stock extends Model
{
    protected $table = 'stock';
    protected $fillable = ['producto_id', 'variante_id', 'local_id', 'cantidad', 'minimo'];

    protected static function booted(): void
    {
        static::saving(function (Stock $s) {
            // si viene de una combinación (variante_id) y no trae producto_id, lo derivamos
            if (empty($s->producto_id) && ! empty($s->variante_id)) {
                $s->producto_id = Variante::where('id', $s->variante_id)->value('producto_id');
            }
        });
    }

    public function producto(): BelongsTo { return $this->belongsTo(Producto::class); }
    public function variante(): BelongsTo { return $this->belongsTo(Variante::class); }
    public function local(): BelongsTo { return $this->belongsTo(Local::class); }
}
