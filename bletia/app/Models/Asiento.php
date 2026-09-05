<?php
namespace App\Models;
use App\Services\Folios;
use Illuminate\Database\Eloquent\Model;
class Asiento extends Model
{
    protected $table = 'asientos';
    protected $fillable = [
        'numero',
        'fecha',
        'glosa',
        'origen',
        'origen_tipo',
        'origen_id',
        'debe',
        'haber',
        'estado',
        'reversa_id',
        'creado_por',
    ];
    protected $casts = ['fecha' => 'date', 'debe' => 'decimal:2', 'haber' => 'decimal:2'];
    public function lineas() { return $this->hasMany(AsientoLinea::class); }
    protected static function booted(): void
    {
        static::creating(function (Asiento $a) {
            if (empty($a->numero) && class_exists(\App\Services\Folios::class)) {
                try { $a->numero = \App\Services\Folios::next('AST'); } catch (\Throwable $e) {}
            }
        });
    }
}
