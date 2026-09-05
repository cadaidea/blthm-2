<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Proveedor extends Model {
    protected $table = 'proveedores';
    protected $fillable = ['nombre', 'email', 'contacto', 'telefono', 'ciudad', 'direccion', 'notas', 'activo'];
    protected $casts = ['activo' => 'boolean'];
}
