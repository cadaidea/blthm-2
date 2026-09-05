<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompraPago extends Model
{
    protected $table = 'compra_pagos';
    protected $fillable = [
        'compra_id',
        'monto',
        'metodo',
        'fecha',
        'tipo_tarjeta',
        'tarjeta_naturaleza',
        'cheque_girador',
        'cheque_numero',
        'cheque_banco',
        'cheque_fecha_cobro',
        'cheque_estado',
        'nro_comprobante',
        'comprobantes',
        'nota',
        'creado_por',
    ];
    protected $casts = ['monto' => 'decimal:2', 'fecha' => 'date', 'cheque_fecha_cobro' => 'date', 'comprobantes' => 'array'];

    public function compra(): BelongsTo { return $this->belongsTo(Compra::class); }
    protected static function booted(): void
    {
        static::created(function (CompraPago $p) { \App\Services\ContabilidadAuto::pagoProveedor($p); });
    }
}
