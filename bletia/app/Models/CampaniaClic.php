<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CampaniaClic extends Model {
    protected $table = 'campania_clics';
    public $timestamps = false;
    protected $fillable = ['campania_email_id', 'url', 'created_at'];
    protected $casts = ['created_at' => 'datetime'];
}
