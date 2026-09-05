<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guardado extends Model
{
    protected $table = 'guardados';
    protected $fillable = ['cliente_id', 'producto_id'];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
