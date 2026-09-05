<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AutomatizacionRun extends Model
{
    protected $table = 'automatizacion_runs';
    public $timestamps = false;
    protected $fillable = ['automatizacion_id','objeto_id','objeto_tipo','campania_id','created_at'];
    protected $casts = ['created_at'=>'datetime'];
}
