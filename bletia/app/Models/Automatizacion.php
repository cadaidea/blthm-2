<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Automatizacion extends Model
{
    protected $table = 'automatizaciones';
    protected $fillable = ['nombre','tipo','estado','lista_ids','asunto','preheader','contenido_json','cuerpo_html','opciones','last_run_at'];
    protected $casts = ['lista_ids'=>'array','opciones'=>'array','last_run_at'=>'datetime'];
    protected static function booted(): void
    {
        static::saving(function ($m) {
            if ($m->isDirty('contenido_json') && $m->contenido_json) {
                $m->cuerpo_html = \App\Support\EditorJsRenderer::render($m->contenido_json);
            }
        });
    }
}
