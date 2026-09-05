<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Empleado extends Model
{
    protected $table = 'empleados';
    protected $fillable = [
        'user_id',
        'editor_id',
        'nombre',
        'slug',
        'identificacion',
        'tipo_identificacion',
        'cargo',
        'email',
        'telefono',
        'direccion',
        'relacion',
        'tipo_contrato',
        'fecha_ingreso',
        'fecha_salida',
        'sueldo',
        'banco',
        'cuenta_bancaria',
        'tipo_cuenta',
        'cargas_familiares',
        'dias_vacaciones_anuales',
        'region',
        'modo_decimo_tercero',
        'modo_decimo_cuarto',
        'modo_fondos_reserva',
        'recibe_fondos_reserva',
        'decimos_mensualizados',
        'activo',
        'notas',
        'bio',
        'foto',
        'web',
        'instagram',
        'facebook',
        'x',
        'linkedin',
    ];
    protected $casts = [
        'fecha_ingreso' => 'date', 'fecha_salida' => 'date',
        'sueldo' => 'decimal:2', 'activo' => 'boolean',
        'recibe_fondos_reserva' => 'boolean', 'decimos_mensualizados' => 'boolean',
        'cargas_familiares' => 'integer',
    ];
    public function rolesPago() { return $this->hasMany(RolPago::class); }
    public const RELACIONES = [
        'dependencia' => 'Relación de dependencia (IESS)',
        'honorarios'  => 'Honorarios (factura, sin IESS)',
        'colaborador' => 'Colaborador / voluntario (sin dependencia, solo incentivos)',
    ];
    public const TIPOS_CONTRATO = [
        'indefinido'  => 'Indefinido',
        'plazo_fijo'  => 'Plazo fijo',
        'ocasional'   => 'Ocasional',
        'eventual'    => 'Eventual',
        'obra_cierta' => 'Obra cierta',
        'temporada'   => 'Temporada',
        'prueba'      => 'Prueba',
    ];

    public function user() { return $this->belongsTo(\App\Models\User::class); }
    public function incentivos() { return $this->hasMany(\App\Models\Incentivo::class); }

    // --- Perfil público de autor del blog (fusionado desde el antiguo modelo Editor) ---
    protected static function bootPerfilAutor(): void
    {
        static::saving(function (self $e) {
            if (! empty($e->slug)) {
                $e->slug = \Illuminate\Support\Str::slug($e->slug);
            }
        });
    }
    public function getRouteKeyName(): string { return 'slug'; }
    public function articulos() { return $this->hasMany(\App\Models\Articulo::class, 'editor_id'); }
    public function getFotoUrlAttribute(): ?string { return $this->foto ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->foto) : null; }
    public function getUrlAttribute(): string { return url('/blog/autor/' . $this->slug); }
}
