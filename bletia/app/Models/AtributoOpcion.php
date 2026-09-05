<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
class AtributoOpcion extends Model {
    protected $table = 'atributo_opciones';
    protected $fillable = ['atributo_id', 'valor', 'color', 'imagen', 'orden'];
    public function atributo(): BelongsTo { return $this->belongsTo(Atributo::class); }
    public function getFotoUrlAttribute(): ?string { return $this->imagen ? Storage::disk('public')->url($this->imagen) : null; }
    public function getEtiquetaAttribute(): string { return ($this->atributo?->nombre ?: '') . ': ' . $this->valor; }
}
