<?php

namespace App\Models;

use App\Services\Folios;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class Reclamo extends Model
{
    protected $table = 'reclamos';
    protected $fillable = [
        'folio',
        'pedido_id',
        'cliente_id',
        'producto',
        'tipo_problema',
        'descripcion',
        'fotos',
        'bultos',
        'estado',
        'resolucion',
        'resolucion_nota',
        'costo',
        'atendido_por',
        'resuelto_at',
    ];
    protected $casts = ['fotos' => 'array', 'bultos' => 'integer', 'costo' => 'decimal:2', 'resuelto_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(function (Reclamo $r) {
            if (empty($r->folio) && Schema::hasColumn('reclamos', 'folio')) {
                $r->folio = Folios::next('RCL');
            }
        });
    }

    public function pedido(): BelongsTo { return $this->belongsTo(Pedido::class, 'pedido_id'); }
    public function cliente(): BelongsTo { return $this->belongsTo(Cliente::class, 'cliente_id'); }
}
