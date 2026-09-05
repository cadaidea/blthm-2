<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CuentaMapeo extends Model
{
    protected $table = 'cuenta_mapeos';
    protected $fillable = [
        'clave',
        'descripcion',
        'codigo_cuenta',
    ];
}
