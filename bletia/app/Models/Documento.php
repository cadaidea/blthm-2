<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Documento extends Model {
    protected $table = 'documentos';
    public $timestamps = false;
    protected $fillable = ['pedido_id', 'despacho_id', 'tipo', 'url', 'ruta', 'nombre_archivo', 'creado_en'];
    protected $casts = ['creado_en' => 'datetime'];
}
