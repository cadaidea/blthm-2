<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostSlug extends Model
{
    protected $fillable = ['articulo_id', 'slug'];

    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }
}
