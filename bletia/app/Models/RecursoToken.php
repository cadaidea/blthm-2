<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RecursoToken extends Model
{
    protected $table = 'recurso_tokens';
    protected $fillable = ['recurso_id','suscriptor_id','token','email','expira_at','usado_at'];
    protected $casts = ['expira_at'=>'datetime','usado_at'=>'datetime'];
    public function recurso(){ return $this->belongsTo(Recurso::class); }
}
