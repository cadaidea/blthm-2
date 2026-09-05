<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SriPuntoEmision extends Model
{
    protected $table = 'sri_puntos_emision';
    protected $fillable = [
        'establecimiento_id',
        'codigo',
        'nombre',
        'activo',
    ];
    protected $casts = ['activo' => 'boolean'];

    public function establecimiento(): BelongsTo { return $this->belongsTo(SriEstablecimiento::class, 'establecimiento_id'); }
}
