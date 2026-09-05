<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Bounce extends Model {
    protected $table = 'bounces';
    public $timestamps = false;
    protected $fillable = ['suscriptor_id', 'email', 'tipo', 'reason', 'source', 'created_at'];
    protected $casts = ['created_at' => 'datetime'];
}
