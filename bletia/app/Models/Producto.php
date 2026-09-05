<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Models\Redirect;

class Producto extends Model
{
    protected $table = 'productos';

    protected $fillable = [
        'categoria_id', 'nombre', 'slug', 'sku', 'descripcion_corta', 'descripcion', 'descripcion_json',
        'precio', 'costo_produccion', 'costo_proveedor', 'iva_rate', 'activo', 'destacado',
        'permitir_pedido', 'mto_texto', 'meta_title', 'meta_description', 'bultos_default', 'dias_fabricacion',
    ];

    protected $casts = [
        'precio'          => 'decimal:2',
        'iva_rate'        => 'decimal:2',
        'activo'          => 'boolean',
        'destacado'       => 'boolean',
        'permitir_pedido' => 'boolean',
    ];

    protected static function booted(): void
    {
        
        static::saving(function ($m) {
            if ($m->isDirty('descripcion_json') && $m->descripcion_json) {
                $m->descripcion = \App\Support\EditorJsRenderer::render($m->descripcion_json);
            }
        });
static::saving(function (Producto $p) {
            if (empty($p->slug) && ! empty($p->nombre)) {
                $p->slug = static::slugUnico($p->nombre, $p->id);
            }
        });
        static::updating(function (Producto $p) {
            if ($p->isDirty('slug') && $p->getOriginal('slug')) {
                Redirect::firstOrCreate(
                    ['from' => '/producto/' . $p->getOriginal('slug')],
                    ['to'   => '/producto/' . $p->slug, 'status' => 301]
                );
            }
        });
        static::saved(function (Producto $p) {
            if ($p->activo) {
                \App\Support\IndexNow::ping(route('tienda.producto', $p->slug));
            }
        });
    }

    public static function slugUnico(string $nombre, ?int $ignoreId = null): string
    {
        $base = Str::slug($nombre);
        $slug = $base; $i = 2;
        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    public function getRouteKeyName(): string { return 'slug'; }

    public function categoria(): BelongsTo { return $this->belongsTo(Categoria::class); }
    public function imagenes(): HasMany { return $this->hasMany(ProductoImagen::class)->orderBy('orden'); }
    public function variantes(): HasMany { return $this->hasMany(Variante::class); }
    public function stock(): HasMany { return $this->hasMany(Stock::class); }

    public function getPrecioConIvaAttribute(): float { return (float) $this->precio; }
    public function getPvpAttribute(): float { return (float) $this->precio; }
    public function getNetoAttribute(): float { return round((float) $this->precio / (1 + (float) $this->iva_rate / 100), 2); }
    public function getIvaMontoAttribute(): float { return round((float) $this->precio - $this->neto, 2); }

    public function getImagenPrincipalAttribute(): ?string
    {
        $img = $this->imagenes->first();
        return $img ? $img->url : null;
    }

    public function getStockTotalAttribute(): int { return (int) $this->stock->sum('cantidad'); }

    public function getBajoPedidoAttribute(): bool
    {
        if ($this->stock_total > 0) return false;
        return $this->permitir_pedido || Ajuste::get('pedido_auto_sin_stock', '0') === '1';
    }

    public function getMtoTextoFinalAttribute(): string
    {
        return $this->mto_texto ?: 'Made to Order · entrega estimada 3–4 semanas';
    }

    public function getComprableAttribute(): bool
    {
        return $this->stock_total > 0 || $this->bajo_pedido;
    }
}