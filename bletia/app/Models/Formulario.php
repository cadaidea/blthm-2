<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Formulario extends Model {
    protected $table = 'formularios';
    protected $fillable = ['nombre', 'tipo', 'estado', 'lista_ids', 'titulo', 'descripcion', 'boton_texto',
        'pedir_nombre', 'opciones', 'impresiones', 'conversiones', 'ubicacion', 'ambito', 'entre_parrafo', 'premarcado', 'imagen'];
    protected $casts = ['lista_ids' => 'array', 'opciones' => 'array', 'pedir_nombre' => 'boolean', 'premarcado' => 'boolean'];
}
