<?php
namespace App\Models;
use App\Services\Folios;
use Illuminate\Database\Eloquent\Model;
class RolPago extends Model
{
    protected $table = 'roles_pago';
    protected $fillable = [
        'folio',
        'empleado_id',
        'anio',
        'mes',
        'relacion',
        'sueldo',
        'horas_extra',
        'horas_suplementarias',
        'horas_extraordinarias',
        'comisiones',
        'bonos',
        'otros_ingresos',
        'total_ingresos',
        'aporte_personal',
        'anticipos',
        'prestamos_iess',
        'otros_descuentos',
        'ret_renta',
        'total_descuentos',
        'aporte_patronal',
        'decimo_tercero',
        'decimo_cuarto',
        'vacaciones',
        'fondos_reserva',
        'liquido',
        'costo_empresa',
        'estado',
        'fecha_pago',
        'metodo_pago',
        'nro_comprobante_pago',
        'banco_pago',
        'adjunto_pago',
        'nota_pago',
        'creado_por',
    ];
    protected $casts = [
        'anio' => 'integer', 'mes' => 'integer', 'fecha_pago' => 'date',
        'sueldo' => 'decimal:2', 'horas_extra' => 'decimal:2', 'comisiones' => 'decimal:2',
        'bonos' => 'decimal:2', 'otros_ingresos' => 'decimal:2', 'total_ingresos' => 'decimal:2',
        'aporte_personal' => 'decimal:2', 'anticipos' => 'decimal:2', 'prestamos_iess' => 'decimal:2',
        'otros_descuentos' => 'decimal:2', 'ret_renta' => 'decimal:2', 'total_descuentos' => 'decimal:2',
        'aporte_patronal' => 'decimal:2', 'decimo_tercero' => 'decimal:2', 'decimo_cuarto' => 'decimal:2',
        'vacaciones' => 'decimal:2', 'fondos_reserva' => 'decimal:2',
        'liquido' => 'decimal:2', 'costo_empresa' => 'decimal:2',
    ];
    public function empleado() { return $this->belongsTo(Empleado::class); }
    protected static function booted(): void
    {
        static::creating(function (RolPago $r) {
            if (empty($r->folio) && class_exists(\App\Services\Folios::class)) {
                try { $r->folio = \App\Services\Folios::next('ROL'); } catch (\Throwable $e) {}
            }
        });
    }
    public function nombreMes(): string
    {
        return [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'][$this->mes] ?? (string)$this->mes;
    }
}
