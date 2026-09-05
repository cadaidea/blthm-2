<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Cuenta extends Model
{
    protected $table = 'cuentas';
    protected $fillable = [
        'codigo',
        'nombre',
        'tipo',
        'naturaleza',
        'padre_id',
        'es_movimiento',
        'activo',
    ];
    protected $casts = ['es_movimiento' => 'boolean', 'activo' => 'boolean'];
    public function padre() { return $this->belongsTo(Cuenta::class, 'padre_id'); }
    public function hijos() { return $this->hasMany(Cuenta::class, 'padre_id'); }
    public function lineas() { return $this->hasMany(AsientoLinea::class, 'cuenta_id'); }
    public function getEtiquetaAttribute(): string { return $this->codigo . ' · ' . $this->nombre; }
}
