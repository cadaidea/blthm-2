<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SriComprobante extends Model
{
    protected $table = 'sri_comprobantes';
    protected $fillable = [
        'tipo',
        'cod_doc',
        'ambiente',
        'estab',
        'pto_emi',
        'secuencial',
        'clave_acceso',
        'estado',
        'numero_autorizacion',
        'fecha_autorizacion',
        'pedido_id',
        'cliente_id',
        'comprobante_ref_id',
        'receptor_tipo_id',
        'receptor_identificacion',
        'receptor_razon',
        'receptor_email',
        'receptor_direccion',
        'receptor_telefono',
        'subtotal',
        'iva',
        'total',
        'detalles',
        'extra',
        'xml_firmado',
        'xml_autorizado',
        'pdf_path',
    ];
    protected $casts = ['detalles' => 'array', 'extra' => 'array', 'fecha_autorizacion' => 'datetime'];
}
