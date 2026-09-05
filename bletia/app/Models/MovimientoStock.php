<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Movimiento de stock (entrada/salida/ajuste/transferencia).
 * Si trae variante_id, aplica el movimiento al stock de ESA combinación exacta.
 * Si no, aplica al stock general del producto (sin variante).
 * No tiene relación con `pedidos` (fabricación es una vía separada).
 */
class MovimientoStock extends Model
{
    protected $table = 'movimientos_stock';
    protected $fillable = ['producto_id', 'variante_id', 'local_id', 'local_destino_id', 'tipo', 'cantidad', 'referencia', 'nota'];

    public function producto(): BelongsTo { return $this->belongsTo(Producto::class); }
    public function variante(): BelongsTo { return $this->belongsTo(Variante::class); }
    public function local(): BelongsTo { return $this->belongsTo(Local::class, 'local_id'); }
    public function localDestino(): BelongsTo { return $this->belongsTo(Local::class, 'local_destino_id'); }

    protected static function booted(): void
    {
        static::created(function (self $m) { $m->aplicar(); });
    }

    /** Aplica el movimiento al stock (de la variante exacta si se indicó, si no al general del producto). */
    public function aplicar(): void
    {
        $set = function (int $localId, callable $fn) {
            $criterio = ['local_id' => $localId];
            if ($this->variante_id) {
                $criterio['variante_id'] = $this->variante_id;
            } else {
                $criterio['producto_id'] = $this->producto_id;
                $criterio['variante_id'] = null;
            }
            $s = Stock::firstOrCreate($criterio, ['cantidad' => 0, 'minimo' => 0, 'producto_id' => $this->producto_id]);
            $s->cantidad = max(0, $fn((int) $s->cantidad));
            $s->save();
        };
        $c = (int) $this->cantidad;
        match ($this->tipo) {
            'entrada'       => $set($this->local_id, fn ($x) => $x + $c),
            'salida'        => $set($this->local_id, fn ($x) => $x - $c),
            'ajuste'        => $set($this->local_id, fn ($x) => $c),
            'transferencia' => (function () use ($set, $c) {
                $set($this->local_id, fn ($x) => $x - $c);
                if ($this->local_destino_id) $set($this->local_destino_id, fn ($x) => $x + $c);
            })(),
            default => null,
        };
    }
}
