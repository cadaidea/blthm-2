<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Despacho extends Model {
    protected $table = 'despachos';
    protected $fillable = ['pedido_id', 'ruta', 'transportista_id', 'local_retiro_id', 'estado',
        'fecha_programada', 'link_id', 'notas', 'listo',
        'conductor_nombre', 'conductor_nui', 'conductor_celular', 'conductor_correo', 'placa'];
    protected $casts = ['fecha_programada' => 'datetime', 'listo' => 'boolean'];
    public function transportista() { return $this->belongsTo(Transportista::class); }
    public function pedido() { return $this->belongsTo(\App\Models\PedidoEspecial::class, 'pedido_id'); }

}
