<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Atributo extends Model {
    protected $table = 'atributos';
    protected $fillable = ['nombre', 'tipo', 'orden'];
    public function opciones(): HasMany { return $this->hasMany(AtributoOpcion::class)->orderBy('orden'); }
}
