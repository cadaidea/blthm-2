<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
class Suscriptor extends Model {
    protected $table = 'suscriptores';
    protected $fillable = ['email', 'nombre', 'apellido', 'estado', 'token', 'ip', 'source', 'confirmed_at', 'unsubscribed_at', 'telefono', 'ciudad', 'nacimiento'];
    protected $casts = ['confirmed_at' => 'datetime', 'unsubscribed_at' => 'datetime'];
    protected static function booted(): void {
        static::creating(function (self $s) { if (empty($s->token)) $s->token = Str::random(64); });
    }
    public function listas(): BelongsToMany { return $this->belongsToMany(Lista::class, 'lista_suscriptor'); }
    public function getNombreCompletoAttribute(): string { return trim(($this->nombre ?? '') . ' ' . ($this->apellido ?? '')) ?: $this->email; }
}
