<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    protected $table = 'clientes';
    protected $fillable = ['identificacion', 'tipo_id', 'nombre', 'email', 'telefono', 'direccion', 'ciudad'];

    public function pedidos(): HasMany { return $this->hasMany(Pedido::class); }
    public function guardados(): HasMany { return $this->hasMany(Guardado::class); }
}
