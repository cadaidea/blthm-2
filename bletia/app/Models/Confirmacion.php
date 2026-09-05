<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Confirmacion extends Model {
    protected $table = 'confirmaciones';
    public $timestamps = false;
    protected $fillable = ['link_id', 'despacho_id', 'pedido_id', 'receptor_nombre', 'receptor_cedula',
        'receptor_celular', 'foto_1_url', 'foto_2_url', 'ip_origen', 'confirmado_en'];
    protected $casts = ['confirmado_en' => 'datetime'];
}
