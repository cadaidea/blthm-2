<?php
namespace App\Models;

use App\Models\PostSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
class Articulo extends Model
{
    protected $table = 'articulos';
    protected $fillable = [
        'blog_categoria_id', 'editor_id', 'titulo', 'slug', 'autor', 'extracto', 'contenido_json', 'contenido',
        'imagen', 'imagen_cabecera', 'bloques', 'activo', 'publicado_at', 'meta_title', 'meta_description',
    ];
    protected $casts = ['activo' => 'boolean', 'imagen_cabecera' => 'boolean', 'publicado_at' => 'datetime', 'contenido_json' => 'array', 'bloques' => 'array'];
    protected static function booted(): void
    {
        
        static::saving(function ($m) {
            if ($m->isDirty('contenido_json') && $m->contenido_json) {
                $m->contenido = \App\Support\EditorJsRenderer::render($m->contenido_json);
            }
        });
static::saving(function (self $a) {
            if (empty($a->slug) && ! empty($a->titulo)) {
                $base = Str::slug($a->titulo); $slug = $base; $i = 2;
                while (static::where('slug', $slug)->where('id', '!=', $a->id)->exists()) $slug = $base . '-' . $i++;
                $a->slug = $slug;
            }
            if ($a->activo && ! $a->publicado_at) $a->publicado_at = now();
        });
        static::updating(function (self $a) {
            if ($a->isDirty('slug') && $a->getOriginal('slug')) {
                PostSlug::firstOrCreate([
                    'articulo_id' => $a->id,
                    'slug'        => $a->getOriginal('slug'),
                ]);
            }
        });
        static::saved(function (self $a) {
            if ($a->activo && ($a->publicado_at === null || $a->publicado_at <= now())) {
                \App\Support\IndexNow::ping($a->url);
            }
        });
    }
    public function getRouteKeyName(): string { return 'slug'; }
    public function slugsAnteriores(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(PostSlug::class, 'articulo_id'); }
    public function categoria(): BelongsTo { return $this->belongsTo(BlogCategoria::class, 'blog_categoria_id'); }
    public function editor(): BelongsTo { return $this->belongsTo(\App\Models\Empleado::class, 'editor_id'); }
    public function etiquetas(): \Illuminate\Database\Eloquent\Relations\BelongsToMany { return $this->belongsToMany(Etiqueta::class, 'articulo_etiqueta'); }
    public function getImagenUrlAttribute(): ?string { return $this->imagen ? Storage::disk('public')->url($this->imagen) : null; }
    public function getUrlAttribute(): string
    {
        $cat = $this->categoria?->slug ?: 'articulos';
        return route('blog.articulo', [$cat, $this->slug]);
    }
    protected static function aplanarTexto($v): string
    {
        if (is_array($v)) {
            $out = '';
            foreach ($v as $item) $out .= ' ' . self::aplanarTexto($item);
            return $out;
        }
        return (string) $v;
    }

    public function getMinutosLecturaAttribute(): int
    {
        $txt = strip_tags((string) $this->contenido);
        foreach ((array) ($this->bloques ?? []) as $b) {
            $tx = data_get($b, 'data.texto', '');
            $ti = data_get($b, 'data.titulo', '');
            $txt .= ' ' . strip_tags(self::aplanarTexto($tx)) . ' ' . self::aplanarTexto($ti);
        }
        $palabras = max(1, str_word_count($txt));
        return max(1, (int) ceil($palabras / 200));
    }
    public function scopePublicado($q) { return $q->where('activo', true)->where(fn ($w) => $w->whereNull('publicado_at')->orWhere('publicado_at', '<=', now())); }
}
