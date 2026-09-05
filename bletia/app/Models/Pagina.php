<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Redirect;

class Pagina extends Model
{
    protected $table = 'paginas';
    protected $fillable = ['titulo', 'slug', 'contenido', 'contenido_json', 'bloques', 'imagen', 'activo', 'mostrar_en_menu', 'orden', 'meta_title', 'meta_description'];
    protected $casts = ['activo' => 'boolean', 'mostrar_en_menu' => 'boolean', 'bloques' => 'array'];

    protected static function booted(): void
    {
        
        static::saving(function ($m) {
            if ($m->isDirty('contenido_json') && $m->contenido_json) {
                $m->contenido = \App\Support\EditorJsRenderer::render($m->contenido_json);
            }
        });
static::saving(function (self $p) {
            if (empty($p->slug) && ! empty($p->titulo)) $p->slug = Str::slug($p->titulo);
        });
        static::updating(function (self $p) {
            if ($p->isDirty('slug') && $p->getOriginal('slug')) {
                Redirect::firstOrCreate(
                    ['from' => '/' . $p->getOriginal('slug')],
                    ['to'   => '/' . $p->slug, 'status' => 301]
                );
            }
        });
    }

    public function getRouteKeyName(): string { return 'slug'; }
    public function getImagenUrlAttribute(): ?string { return $this->imagen ? Storage::disk('public')->url($this->imagen) : null; }
}