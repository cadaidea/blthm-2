<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ProductoMaterial extends Model
{
    protected $table = 'producto_materiales';
    protected $fillable = [
        'producto_id',
        'materia_prima_id',
        'cantidad',
        'nota',
    ];
    protected $casts = ['cantidad' => 'decimal:3'];
    public function producto() { return $this->belongsTo(Producto::class, 'producto_id'); }
    public function materia() { return $this->belongsTo(MateriaPrima::class, 'materia_prima_id'); }
}
