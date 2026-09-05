<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Recurso extends Model
{
    protected $table = 'recursos';
    protected $fillable = ['nombre','slug','descripcion','tipo','archivo','cupon_codigo','cupon_id','lista_ids','activo','descargas'];
    protected $casts = ['lista_ids'=>'array','activo'=>'bool'];
}
