<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PedidoItemErp extends Model {
    protected $table = 'pedido_items';
    protected $fillable = [
        'pedido_id',
        'producto_id',
        'nombre',
        'variantes',
        'precio',
        'iva_rate',
        'cantidad',
        'subtotal',
        'proveedor_id',
        'bultos',
        'tapiz_principal',
        'tapiz_secundario',
        'cojines',
        'lacado',
        'notas_adicionales',
        'local_origen_id',
        'fotos_ref',
        'cojines_secundario',
        'foto_modelo',
        'foto_tapiz_principal',
        'foto_tapiz_secundario',
        'foto_cojines',
        'foto_cojines_secundario',
        'foto_lacado',
        'pvp_base',
        'descuento_pct',
        'valor_adicional',
        'motivo_adicional',
        'foto_adicional',
        'lado',
    ];
    public $timestamps = false;
    protected $casts = ['fotos_ref' => 'array'];
}
