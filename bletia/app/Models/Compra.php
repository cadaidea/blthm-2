<?php

namespace App\Models;

use App\Services\Folios;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Abastecimiento de stock: Compra a proveedor o Orden de producción (taller).
 * Es una vía SEPARADA de pedidos (venta a cliente) y de venta directa de stock.
 * Aquí el "cliente" eres tú mismo: compras/produces para llenar tu inventario.
 *
 * Flujo: creada → en_proceso → listo_envio → en_transito → recibida (ahí se suma stock).
 */
class Compra extends Model
{
    protected $table = 'compras';
    protected $fillable = [
        'folio',
        'tipo',
        'proveedor_id',
        'local_destino_id',
        'estado',
        'doc_tipo',
        'doc_numero',
        'doc_fecha',
        'sustento_tributario',
        'autorizacion_sri',
        'subtotal',
        'iva',
        'total',
        'ret_iva',
        'ret_renta',
        'ret_comprobante',
        'ret_fecha',
        'notas',
        'creado_por',
        'recibida_at',
    ];
    protected $casts = [
        'subtotal' => 'decimal:2', 'iva' => 'decimal:2', 'total' => 'decimal:2',
        'doc_fecha' => 'date', 'recibida_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Compra $c) {
            if (empty($c->folio)) {
                // OC = Orden de Compra (proveedor externo) · OP = Orden de Producción (taller propio)
                $prefijo = $c->tipo === 'produccion_interna' ? 'OP' : 'OC';
                $c->folio = Folios::next($prefijo);
            }
        });
        static::updated(function (Compra $c) {
            if ($c->wasChanged('estado') && $c->estado === 'recibida') {
                \App\Services\ContabilidadAuto::compra($c);
            }
            if ($c->wasChanged('estado') && $c->estado === 'anulada') {
                \App\Services\ContabilidadAuto::reversarDe('Compra', $c->id);
            }
        });
    }

    public function items(): HasMany { return $this->hasMany(CompraItem::class); }
    public function pagos(): HasMany { return $this->hasMany(CompraPago::class); }
    public function proveedor(): BelongsTo { return $this->belongsTo(Proveedor::class); }
    public function localDestino(): BelongsTo { return $this->belongsTo(Local::class, 'local_destino_id'); }

    public function totalPagado(): float
    {
        return (float) $this->pagos()->sum('monto');
    }

    public function saldo(): float
    {
        return round((float) $this->total - $this->totalPagado(), 2);
    }
}
