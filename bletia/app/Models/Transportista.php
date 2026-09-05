<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Transportista extends Model {
    protected $table = 'transportistas';
    protected $fillable = ['nombre', 'email', 'celular', 'celular2', 'empresa', 'activo',
        'tipo_identificacion', 'identificacion', 'direccion'];
    protected $casts = ['activo' => 'boolean'];
}
