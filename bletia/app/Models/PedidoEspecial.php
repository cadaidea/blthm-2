<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PedidoEspecial extends Model
{
    protected $table = 'pedidos';
    protected $fillable = [
        'codigo',
        'cliente_id',
        'vendedor_id',
        'local_id',
        'estado',
        'bodega_despacho_id',
        'despachado_at',
        'subtotal',
        'iva',
        'total',
        'pp_client_tx',
        'pp_transaction_id',
        'pp_auth',
        'email',
        'estado_erp',
        'tipo_erp',
        'origen',
        'codigo_origen',
        'observacion_anulacion',
        'anulado_at',
        'folio',
        'folio_of',
        'folio_anulacion',
        'fecha_entrega',
        'vendido_por',
        'vendido_at',
        'aprobado_por',
        'aprobado_at',
        'enviado_fab_por',
        'enviado_fab_at',
        'despachado_por',
        'despachado_por_at',
        'fecha_solicitada',
        'fecha_comprometida',
        'forma_venta',
        'retira_local',
        'direccion_envio',
        'ciudad_envio',
        'contacto_envio',
        'destino_fab',
        'nro_factura',
        'facturado_at',
        'facturado_por',
        'cupon_id',
        'cupon_codigo',
        'descuento',
        'of_aceptada_at',
        'of_aceptada_por',
        'nombre_recibe',
        'horario_entrega',
        'anticipo_solicitado_at',
        'woo_id',
    ];
    public $timestamps = false;

    protected $casts = ['anulado_at' => 'datetime'];

    public function cliente()
    {
        return $this->belongsTo(\App\Models\Cliente::class, 'cliente_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PedidoItemErp::class, 'pedido_id');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(\App\Models\PedidoHistorial::class, 'pedido_id')->orderBy('id');
    }
}
