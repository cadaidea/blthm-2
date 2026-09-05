<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Models\Redirect;

class BlogCategoria extends Model
{
    protected $table = 'blog_categorias';
    protected $fillable = ['nombre', 'slug', 'orden', 'activo'];
    protected $casts = ['activo' => 'boolean'];

    protected static function booted(): void
    {
        static::saving(function (self $c) {
            if (empty($c->slug) && ! empty($c->nombre)) $c->slug = Str::slug($c->nombre);
        });
        static::updating(function (self $c) {
            if ($c->isDirty('slug') && $c->getOriginal('slug')) {
                Redirect::firstOrCreate(
                    ['from' => '/blog/categoria/' . $c->getOriginal('slug')],
                    ['to'   => '/blog/categoria/' . $c->slug, 'status' => 301]
                );
            }
        });
    }

    public function getRouteKeyName(): string { return 'slug'; }
    public function articulos(): HasMany { return $this->hasMany(Articulo::class); }
}