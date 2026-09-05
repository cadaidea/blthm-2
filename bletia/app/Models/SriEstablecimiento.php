<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SriEstablecimiento extends Model
{
    protected $table = 'sri_establecimientos';
    protected $fillable = [
        'codigo',
        'nombre',
        'direccion',
        'activo',
    ];
    protected $casts = ['activo' => 'boolean'];

    public function puntos(): HasMany { return $this->hasMany(SriPuntoEmision::class, 'establecimiento_id'); }
}
