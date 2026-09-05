<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CuponUso extends Model
{
    protected $table = 'cupon_usos';
    protected $fillable = ['cupon_id','cliente_id','pedido_id','monto'];
}
