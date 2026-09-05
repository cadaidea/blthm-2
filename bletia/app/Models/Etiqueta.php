<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
class Etiqueta extends Model {
    protected $table = 'etiquetas';
    protected $fillable = ['nombre', 'slug'];
    protected static function booted(): void {
        static::saving(function (self $e) { if (empty($e->slug) && !empty($e->nombre)) $e->slug = Str::slug($e->nombre); });
    }
    public function getRouteKeyName(): string { return 'slug'; }
    public function articulos(): BelongsToMany { return $this->belongsToMany(Articulo::class, 'articulo_etiqueta'); }
    public function getUrlAttribute(): string { return url('/blog/etiqueta/' . $this->slug); }
}
