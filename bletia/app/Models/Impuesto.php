<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Impuesto extends Model
{
    protected $table = 'impuestos';
    protected $fillable = [
        'nombre',
        'tipo',
        'porcentaje',
        'codigo_sri',
        'vigente_desde',
        'vigente_hasta',
        'activo',
    ];
    protected $casts = [
        'porcentaje'    => 'decimal:2',
        'vigente_desde' => 'date',
        'vigente_hasta' => 'date',
        'activo'        => 'boolean',
    ];
}
