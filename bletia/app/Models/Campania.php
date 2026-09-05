<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Campania extends Model {
    protected $table = 'campanias';
    protected $fillable = ['asunto', 'preheader', 'contenido_json', 'cuerpo_html', 'lista_ids', 'estado', 'programada_at', 'enviada_at',
        'total_destinatarios', 'total_enviados', 'total_aperturas', 'total_clics'];
    protected $casts = ['lista_ids' => 'array', 'programada_at' => 'datetime', 'enviada_at' => 'datetime'];
    protected static function booted(): void
    {
        static::saving(function ($m) {
            if ($m->isDirty('contenido_json') && $m->contenido_json) {
                $m->cuerpo_html = \App\Support\EditorJsRenderer::render($m->contenido_json);
            }
        });
    }
    public function emails(): HasMany { return $this->hasMany(CampaniaEmail::class); }
}
