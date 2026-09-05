<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Models\Redirect;

class Categoria extends Model
{
    protected $table = 'categorias';

    protected $fillable = [
        'imagen',
        'parent_id', 'nombre', 'slug', 'descripcion', 'orden', 'activo',
        'meta_title', 'meta_description',
    ];

    protected $casts = ['activo' => 'boolean'];

    protected static function booted(): void
    {
        static::saving(function (Categoria $c) {
            if (empty($c->slug) && ! empty($c->nombre)) {
                $base = Str::slug($c->nombre);
                $slug = $base; $i = 2;
                while (static::where('slug', $slug)->when($c->id, fn ($q) => $q->where('id', '!=', $c->id))->exists()) {
                    $slug = $base . '-' . $i++;
                }
                $c->slug = $slug;
            }
        });
        static::updating(function (Categoria $c) {
            if ($c->isDirty('slug') && $c->getOriginal('slug')) {
                Redirect::firstOrCreate(
                    ['from' => '/categoria/' . $c->getOriginal('slug')],
                    ['to'   => '/categoria/' . $c->slug, 'status' => 301]
                );
            }
        });
    }

    public function getRouteKeyName(): string { return 'slug'; }

    public function parent(): BelongsTo { return $this->belongsTo(Categoria::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(Categoria::class, 'parent_id'); }
    public function productos(): HasMany { return $this->hasMany(Producto::class); }
}