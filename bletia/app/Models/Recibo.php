<?php
namespace App\Models;

use App\Services\Folios;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class Recibo extends Model
{
    protected $table = 'recibos';
    protected $fillable = [
        'pedido_id',
        'venta_id',
        'cliente_id',
        'tipo',
        'monto',
        'metodo',
        'fecha',
        'nota',
        'folio',
        'validado',
        'validado_por',
        'validado_at',
        'comprobantes',
        'nro_comprobante',
        'lote',
        'tipo_tarjeta',
        'tarjeta_naturaleza',
        'cheque_girador',
        'cheque_numero',
        'cheque_banco',
        'cheque_fecha_cobro',
        'cheque_cobrado',
        'cheque_cobrado_at',
        'cheque_estado',
        'cheque_motivo',
        'cheque_reemplazo_id',
        'cheque_foto_comprobante',
        'cheque_num_deposito',
        'cheque_sustento_sri',
        'recibido_por',
        'resolucion',
        'resuelto_por',
        'resuelto_at',
        'pagador_nombre',
        'pagador_id_num',
        'pagador_contacto',
        'pagador_email',
    ];
    protected $casts = ['monto' => 'decimal:2', 'fecha' => 'date', 'comprobantes' => 'array', 'validado' => 'boolean', 'validado_at' => 'datetime', 'resuelto_at' => 'datetime', 'cheque_fecha_cobro' => 'date', 'cheque_cobrado' => 'boolean', 'cheque_cobrado_at' => 'datetime', 'cheque_estado' => 'string'];

    protected static function booted(): void
    {
        static::creating(function (Recibo $r) {
            if (empty($r->folio) && Schema::hasColumn('recibos', 'folio')) {
                $r->folio = Folios::next('REC');
            }
        });
        static::created(function (Recibo $r) { \App\Services\ContabilidadAuto::cobro($r); });
    }

    public function pedido(): BelongsTo { return $this->belongsTo(Pedido::class, 'pedido_id'); }
    public function cliente(): BelongsTo { return $this->belongsTo(Cliente::class, 'cliente_id'); }
}
