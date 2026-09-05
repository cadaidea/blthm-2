<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProductoImagen extends Model
{
    protected $table = 'producto_imagenes';
    protected $fillable = ['producto_id', 'ruta', 'alt', 'orden'];

    public function producto(): BelongsTo { return $this->belongsTo(Producto::class); }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->ruta);
    }
}
