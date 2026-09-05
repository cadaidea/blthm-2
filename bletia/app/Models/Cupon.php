<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Cupon extends Model
{
    protected $table = 'cupones';
    protected $fillable = ['codigo','tipo','valor','audiencia','activo','limite_global','vence_at','minimo_compra','usos'];
    protected $casts = ['activo'=>'bool','valor'=>'decimal:2','vence_at'=>'date','minimo_compra'=>'decimal:2'];
}
