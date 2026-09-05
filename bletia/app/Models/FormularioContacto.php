<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class FormularioContacto extends Model
{
    protected $table = 'formulario_contactos';
    protected $fillable = ['nombre', 'slug', 'correo_destino', 'temas', 'mensaje_exito', 'activo'];
    protected $casts = ['activo' => 'boolean'];

    public function temasArray(): array
    {
        $raw = trim((string) $this->temas);
        if (! $raw) return [];
        return array_values(array_filter(array_map('trim', explode("\n", $raw))));
    }
}
