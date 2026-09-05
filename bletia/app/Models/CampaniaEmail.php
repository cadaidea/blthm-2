<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class CampaniaEmail extends Model {
    protected $table = 'campania_emails';
    public $timestamps = false;
    protected $fillable = ['campania_id', 'suscriptor_id', 'estado', 'tracking_token', 'intentos', 'error', 'enviado_at', 'abierto_at', 'clics'];
    protected $casts = ['enviado_at' => 'datetime', 'abierto_at' => 'datetime'];
    public function campania(): BelongsTo { return $this->belongsTo(Campania::class); }
    public function suscriptor(): BelongsTo { return $this->belongsTo(Suscriptor::class); }
}
