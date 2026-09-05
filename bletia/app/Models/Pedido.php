<?php

namespace App\Models;

use App\Services\Folios;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class Pedido extends Model
{
    protected $table = 'pedidos';

    protected $fillable = [
        'codigo', 'cliente_id', 'estado', 'bodega_despacho_id', 'despachado_at',
        'subtotal', 'iva', 'total', 'pp_client_tx', 'pp_transaction_id', 'pp_auth', 'email',
        'cupon_id', 'cupon_codigo', 'descuento',
        'origen', 'codigo_origen',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2', 'iva' => 'decimal:2', 'total' => 'decimal:2', 'despachado_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Pedido $p) {
            // Canal único de pedidos: folio SIEMPRE PED, correlativo continuo y atómico.
            if (empty($p->folio) && Schema::hasColumn('pedidos', 'folio')) {
                $p->folio = Folios::next('PED');
            }

            // Registrar origen automáticamente si no viene definido.
            if (Schema::hasColumn('pedidos', 'origen') && empty($p->origen)) {
                $p->origen = $p->woo_id ? 'woo' : (($p->tipo_erp ?? null) === 'local' ? 'local' : 'local');
            }
            if (Schema::hasColumn('pedidos', 'codigo_origen') && empty($p->codigo_origen) && ! empty($p->woo_id)) {
                $p->codigo_origen = 'woo #' . $p->woo_id;
            }
        });
    }

    /** Código principal visible (PED-XXXXXX). */
    public function getCodigoPrincipalAttribute(): string
    {
        return $this->folio ?: ($this->codigo ?: ('#' . $this->id));
    }

    /** Etiqueta de origen para mostrar pequeña al lado (ej. "web #1045"). Null si es local sin código. */
    public function getEtiquetaOrigenAttribute(): ?string
    {
        if (! $this->codigo_origen) return null;
        // no repetir si el código de origen es un folio legado idéntico al actual
        if ($this->codigo_origen === $this->folio) return null;
        return $this->codigo_origen;
    }

    public function cliente(): BelongsTo { return $this->belongsTo(Cliente::class); }
    public function items(): HasMany { return $this->hasMany(PedidoItem::class); }
    public function bodegaDespacho(): BelongsTo { return $this->belongsTo(Local::class, 'bodega_despacho_id'); }

    public function getRouteKeyName(): string { return 'codigo'; }
}
