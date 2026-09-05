<?php
namespace App\Models;
use App\Services\Folios;
use Illuminate\Database\Eloquent\Model;
class Gasto extends Model
{
    protected $table = 'gastos';
    protected $fillable = [
        'folio',
        'fecha',
        'categoria',
        'proveedor_id',
        'beneficiario',
        'beneficiario_id_num',
        'doc_tipo',
        'doc_numero',
        'autorizacion_sri',
        'base',
        'iva',
        'ret_iva',
        'ret_renta',
        'total',
        'forma_pago',
        'metodo_pago',
        'notas',
        'adjunto',
        'estado',
        'creado_por',
    ];
    protected $casts = [
        'fecha' => 'date', 'base' => 'decimal:2', 'iva' => 'decimal:2',
        'ret_iva' => 'decimal:2', 'ret_renta' => 'decimal:2', 'total' => 'decimal:2',
    ];
    public function proveedor() { return $this->belongsTo(Proveedor::class); }
    protected static function booted(): void
    {
        static::creating(function (Gasto $g) {
            if (empty($g->folio) && class_exists(\App\Services\Folios::class)) {
                try { $g->folio = \App\Services\Folios::next('GAS'); } catch (\Throwable $e) {}
            }
            $g->total = round((float)$g->base + (float)$g->iva - (float)$g->ret_iva - (float)$g->ret_renta, 2);
        });
    }
    public const CATEGORIAS = [
        'combustible' => 'Combustible',
        'transporte' => 'Transporte y flete',
        'viaticos' => 'Viáticos (alimentación/hospedaje/viajes)',
        'marketing' => 'Marketing y publicidad externa',
        'servicios_basicos' => 'Servicios básicos (luz/agua/internet)',
        'arriendo' => 'Arriendo',
        'suministros' => 'Suministros y materiales',
        'comisiones' => 'Comisiones bancarias',
        'sueldos' => 'Sueldos y honorarios',
        'varios' => 'Varios',
    ];
}
