<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompraItem extends Model
{
    protected $table = 'compra_items';
    protected $fillable = [
        'compra_id',
        'producto_id',
        'variante_id',
        'nombre',
        'cantidad',
        'bultos',
        'costo_unitario',
        'iva_rate',
        'subtotal',
        'tapiz_principal',
        'foto_tapiz_principal',
        'tapiz_secundario',
        'foto_tapiz_secundario',
        'cojines',
        'foto_cojines',
        'lacado',
        'foto_lacado',
        'notas_adicionales',
    ];
    protected $casts = ['cantidad' => 'decimal:2', 'costo_unitario' => 'decimal:2', 'iva_rate' => 'decimal:2', 'subtotal' => 'decimal:2'];

    public function compra(): BelongsTo { return $this->belongsTo(Compra::class); }
    public function producto(): BelongsTo { return $this->belongsTo(Producto::class); }
    public function variante(): BelongsTo { return $this->belongsTo(Variante::class); }
}
