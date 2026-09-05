<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Variante extends Model
{
    protected $table = 'variantes';

    protected $fillable = [
        'producto_id', 'atributo_opcion_id', 'nombre', 'valor',
        'color', 'foto', 'precio_extra', 'pvp', 'costo', 'opciones',
    ];

    protected $casts = [
        'precio_extra' => 'decimal:2',
        'pvp'          => 'decimal:2',
        'costo'        => 'decimal:2',
        'opciones'     => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $v) {
            // Limpia opciones
            $op = collect((array) $v->opciones)
                ->filter(fn ($x) => filled($x))
                ->map(fn ($x) => (int) $x)
                ->all();
            $v->opciones = $op ?: null;

            // Si foto llega como array (Filament temporal), toma el primer valor
            if (is_array($v->foto)) {
                $v->foto = collect($v->foto)->filter()->first();
            }
        });
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function opcion(): BelongsTo
    {
        return $this->belongsTo(AtributoOpcion::class, 'atributo_opcion_id');
    }

    public function stock(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Stock::class, 'variante_id');
    }

    public function getStockTotalAttribute(): int
    {
        return (int) $this->stock->sum('cantidad');
    }

    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto ? Storage::disk('public')->url($this->foto) : null;
    }

    /** PVP efectivo de la variante (si no tiene, usa el del producto). */
    public function getPvpFinalAttribute(): float
    {
        return (float) ($this->pvp ?: optional($this->producto)->precio ?: 0);
    }
    /** Costo efectivo de la variante (si no tiene, usa el costo de producción/proveedor del producto). */
    public function getCostoFinalAttribute(): float
    {
        if ($this->costo) return (float) $this->costo;
        $p = $this->producto;
        return (float) ($p->costo_produccion ?: $p->costo_proveedor ?: 0);
    }

    /** IDs de opción elegidos (limpios). */
    public function opcionIds(): array
    {
        return collect((array) $this->opciones)
            ->map(fn ($v) => (int) $v)
            ->filter()
            ->values()
            ->all();
    }

    /** Etiqueta legible de la combinación: "Tapiz: Beige · Lado: Left". */
    public function getComboLabelAttribute(): string
    {
        $ids = $this->opcionIds();

        if (! $ids) {
            return trim(($this->nombre ? $this->nombre . ': ' : '') . ($this->valor ?? ''), ': ');
        }

        $ops = AtributoOpcion::with('atributo')->whereIn('id', $ids)->get();

        return $ops->map(fn ($o) => ($o->atributo?->nombre ? $o->atributo->nombre . ': ' : '') . $o->valor)->implode(' · ');
    }
}