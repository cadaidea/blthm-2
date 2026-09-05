<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
class Editor extends Model {
    protected $table = 'editores';
    protected $fillable = ['nombre', 'slug', 'cargo', 'bio', 'foto', 'web', 'instagram', 'facebook', 'x', 'linkedin'];
    protected static function booted(): void {
        static::saving(function (self $e) { if (empty($e->slug) && !empty($e->nombre)) $e->slug = Str::slug($e->nombre); });
    }
    public function getRouteKeyName(): string { return 'slug'; }
    public function articulos(): HasMany { return $this->hasMany(Articulo::class); }
    public function getFotoUrlAttribute(): ?string { return $this->foto ? Storage::disk('public')->url($this->foto) : null; }
    public function getUrlAttribute(): string { return url('/blog/autor/' . $this->slug); }
}
