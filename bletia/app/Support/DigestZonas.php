<?php

namespace App\Support;

use App\Models\Formulario;
use Illuminate\Support\Facades\View;

class DigestZonas
{
    public static function formsDe(string $zona)
    {
        return Formulario::where('estado', 'activo')->where('tipo', 'inline')->where('ubicacion', $zona)->get();
    }

    /** HTML de todos los formularios inline de una zona. */
    public static function render(string $zona): string
    {
        $html = '';
        foreach (self::formsDe($zona) as $f) {
            $html .= View::make('tienda.partials.digest-inline', [
                'form' => $f,
                'premarcado' => $zona === 'checkout' && $f->premarcado,
            ])->render();
        }
        return $html;
    }

    /** Inyecta los formularios 'blog_entre_parrafos' en el contenido HTML del artículo. */
    public static function inyectarEnContenido(string $html): string
    {
        $forms = self::formsDe('blog_entre_parrafos');
        if ($forms->isEmpty()) {
            return $html;
        }
        $partes = preg_split('/(<\/p>)/i', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        // Reconstruir en bloques "<...>...</p>"
        $parrafos = [];
        $buffer = '';
        foreach ($partes as $trozo) {
            $buffer .= $trozo;
            if (preg_match('/<\/p>$/i', $trozo)) {
                $parrafos[] = $buffer;
                $buffer = '';
            }
        }
        if ($buffer !== '') {
            $parrafos[] = $buffer;
        }

        foreach ($forms as $f) {
            $pos = max(1, (int) ($f->entre_parrafo ?: 2));
            $bloque = View::make('tienda.partials.digest-inline', ['form' => $f, 'premarcado' => false])->render();
            if (isset($parrafos[$pos - 1])) {
                $parrafos[$pos - 1] .= $bloque;
            } else {
                $parrafos[count($parrafos) - 1] = ($parrafos[count($parrafos) - 1] ?? '') . $bloque;
            }
        }
        return implode('', $parrafos);
    }
}
