<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $table = 'ventas';
    protected $fillable = [
        'pedido_id',
        'nro_factura',
        'folio',
        'tipo_comprobante',
        'numero_comprobante',
        'sri_comprobante_id',
        'fecha',
        'cliente_id',
        'local_id',
        'vendedor_id',
        'forma_venta',
        'origen',
        'codigo_origen',
        'info_adicional',
        'es_credito',
        'credito_plazo_dias',
        'credito_vence_at',
        'saldo_credito',
        'subtotal',
        'iva',
        'total',
        'ret_iva',
        'ret_renta',
        'ret_comprobante',
        'ret_fecha',
        'estado',
        'facturado_por',
        'facturado_at',
    ];
    protected static function booted(): void
    {
        static::created(function (Venta $v) { \App\Services\ContabilidadAuto::venta($v); });
    }
    protected $casts = [
        'fecha'              => 'date',
        'facturado_at'       => 'datetime',
        'subtotal'           => 'decimal:2',
        'iva'                => 'decimal:2',
        'total'              => 'decimal:2',
        'es_credito'         => 'boolean',
        'credito_vence_at'   => 'date',
        'saldo_credito'      => 'decimal:2',
        'credito_plazo_dias' => 'integer',
    ];

    public function pedido()   { return $this->belongsTo(Pedido::class); }
    public function cliente()  { return $this->belongsTo(Cliente::class); }
    public function local()    { return $this->belongsTo(Local::class); }
    public function vendedor() { return $this->belongsTo(User::class, 'vendedor_id'); }
    public function sriComprobante() { return $this->belongsTo(SriComprobante::class, 'sri_comprobante_id'); }

    public function getTipoComprobanteLabelAttribute(): string
    {
        return match ($this->tipo_comprobante) {
            'factura' => 'Factura',
            'nota_venta' => 'Nota de venta',
            default => '—',
        };
    }

    public function esFactura(): bool { return $this->tipo_comprobante === 'factura'; }

    /** Estado del crédito: 'al_dia' | 'por_vencer' | 'vencido' | null si no es crédito. */
    public function estadoCredito(): ?string
    {
        if (! $this->es_credito || (float) $this->saldo_credito <= 0) return null;
        if (! $this->credito_vence_at) return 'al_dia';
        $dias = now()->startOfDay()->diffInDays($this->credito_vence_at->startOfDay(), false);
        if ($dias < 0) return 'vencido';
        if ($dias <= 7) return 'por_vencer';
        return 'al_dia';
    }
}
