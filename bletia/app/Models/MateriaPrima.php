<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MateriaPrima extends Model
{
    protected $table = 'materias_primas';
    protected $fillable = [
        'nombre',
        'unidad',
        'stock',
        'minimo',
        'costo',
        'activo',
    ];
    protected $casts = ['stock' => 'decimal:2', 'minimo' => 'decimal:2', 'costo' => 'decimal:2', 'activo' => 'boolean'];

    public function movimientos(): HasMany { return $this->hasMany(MovimientoMaterial::class); }

    public function bajoMinimo(): bool { return (float) $this->stock <= (float) $this->minimo; }
}
