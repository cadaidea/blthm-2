<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AsientoLinea extends Model
{
    protected $table = 'asiento_lineas';
    protected $fillable = [
        'asiento_id',
        'cuenta_id',
        'debe',
        'haber',
        'detalle',
    ];
    protected $casts = ['debe' => 'decimal:2', 'haber' => 'decimal:2'];
    public function asiento() { return $this->belongsTo(Asiento::class); }
    public function cuenta() { return $this->belongsTo(Cuenta::class); }
}
