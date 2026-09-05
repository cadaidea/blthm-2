<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AvisoStock extends Model
{
    protected $table = 'avisos_stock';
    protected $fillable = ['producto_id','email','notificado','notificado_at'];
    protected $casts = ['notificado'=>'bool','notificado_at'=>'datetime'];
}
