<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Ajuste extends Model
{
    protected $table = 'ajustes';
    protected $fillable = ['clave', 'valor'];
    public $timestamps = true;

    public static function defaults(): array
    {
        return [
            'color_primario'  => '#161921',
            'color_secundario'=> '#0499FC',
            'color_footer'    => '#EFEBDD',
            'logo'            => '',   // svg (cabecera fija)
            'logo_claro'      => '',   // svg (cabecera transparente)
            'logo_movil'      => '',   // svg/png móvil
            'favicon'         => '',   // png
            'pedido_auto_sin_stock' => '0', // '1' = todo bajo pedido si stock 0
            'home_hero_img' => '', 'home_hero_titulo' => '', 'home_hero_texto' => '', 'home_hero_cta' => '', 'home_hero_cta_url' => '',
            'home_intro_titulo' => '', 'home_intro_texto' => '', 'home_producto_id' => '',
            'footer_texto' => '', 'footer_nosotros' => '', 'footer_legal' => '',
        ];
    }

    public static function get(string $clave, $default = null)
    {
        $all = Cache::rememberForever('ajustes_all', function () {
            return static::pluck('valor', 'clave')->toArray();
        });
        if (array_key_exists($clave, $all) && $all[$clave] !== null && $all[$clave] !== '') {
            return $all[$clave];
        }
        $d = static::defaults();
        return $default ?? ($d[$clave] ?? null);
    }

    public static function set(string $clave, $valor): void
    {
        static::updateOrCreate(['clave' => $clave], ['valor' => $valor]);
        Cache::forget('ajustes_all');
    }
}
