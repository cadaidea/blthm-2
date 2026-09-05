<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Local extends Model
{
    protected $table = 'locales';
    protected $fillable = ['nombre', 'tipo', 'activo', 'direccion', 'ciudad'];
    protected $casts = ['activo' => 'boolean'];

    public function stock(): HasMany { return $this->hasMany(Stock::class); }
}
