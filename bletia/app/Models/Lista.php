<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
class Lista extends Model {
    protected $table = 'listas';
    protected $fillable = ['nombre', 'slug', 'descripcion', 'publica'];
    protected $casts = ['publica' => 'boolean'];
    protected static function booted(): void {
        static::saving(function (self $l) { if (empty($l->slug) && !empty($l->nombre)) $l->slug = Str::slug($l->nombre); });
    }
    public function suscriptores(): BelongsToMany { return $this->belongsToMany(Suscriptor::class, 'lista_suscriptor'); }
}
