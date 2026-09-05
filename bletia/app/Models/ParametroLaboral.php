<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ParametroLaboral extends Model
{
    protected $table = 'parametros_laborales';
    protected $fillable = [
        'anio',
        'sbu',
        'aporte_personal',
        'aporte_patronal',
        'fondos_reserva',
    ];
    protected $casts = ['anio' => 'integer', 'sbu' => 'decimal:2', 'aporte_personal' => 'decimal:2', 'aporte_patronal' => 'decimal:2', 'fondos_reserva' => 'decimal:2'];
}
