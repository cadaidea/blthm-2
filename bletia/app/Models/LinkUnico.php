<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LinkUnico extends Model {
    protected $table = 'links_unicos';
    protected $fillable = ['token', 'tipo', 'pedido_id', 'despacho_id', 'reclamo_id', 'compra_id', 'usado', 'intentos', 'expira_en'];
    protected $casts = ['usado' => 'boolean', 'expira_en' => 'datetime'];
    public function vigente(): bool { return ! $this->usado && (! $this->expira_en || $this->expira_en->isFuture()); }
}
