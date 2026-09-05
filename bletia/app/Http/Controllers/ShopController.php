<?php

namespace App\Http\Controllers;

use App\Models\AtributoOpcion;
use App\Models\Categoria;
use App\Models\Producto;

class ShopController extends Controller
{
    public function index()
    {
        $categorias = Categoria::where('activo', true)->orderBy('orden')->get();

        // Mapa opciones: id => modelo (atributo_id, valor, color, atributo.nombre)
        $opMap = AtributoOpcion::with('atributo')->get()->keyBy('id');

        $productos = Producto::where('activo', true)
            ->with(['imagenes', 'variantes', 'categoria'])
            ->orderByDesc('destacado')->latest()
            ->get();

        $items = $productos->map(function ($p) use ($opMap) {
            $porAttr = [];
            foreach ($p->variantes as $v) {
                $op = $v->atributo_opcion_id ? ($opMap[$v->atributo_opcion_id] ?? null) : null;
                if ($op) {
                    $porAttr[$op->atributo_id][] = $op->valor;
                }
            }
            foreach ($porAttr as $aid => $vals) {
                $porAttr[$aid] = array_values(array_unique($vals));
            }

            return [
                'producto'  => $p,
                'precio'    => (float) $p->precio,
                'cat'       => $p->categoria_id,
                'opciones'  => $porAttr,
                'destacado' => (bool) $p->destacado,
            ];
        });

        $precios = $items->pluck('precio')->filter();
        $min = (int) floor($precios->min() ?? 0);
        $max = (int) ceil($precios->max() ?? 1000);
        if ($max <= $min) {
            $max = $min + 100;
        }

        // Filtros: por atributo, solo opciones usadas (con color). Estructura lista para la vista.
        $usoColor = []; // atributo_id => [valor => color]
        $usoNombre = []; // atributo_id => nombre atributo
        foreach ($items as $it) {
            foreach ($it['opciones'] as $aid => $vals) {
                foreach ($vals as $val) {
                    if (! isset($usoColor[$aid][$val])) {
                        $usoColor[$aid][$val] = null;
                    }
                }
            }
        }
        foreach ($opMap as $op) {
            if (isset($usoColor[$op->atributo_id]) && array_key_exists($op->valor, $usoColor[$op->atributo_id])) {
                $usoColor[$op->atributo_id][$op->valor] = $op->color;
                $usoNombre[$op->atributo_id] = optional($op->atributo)->nombre ?: ('Atributo ' . $op->atributo_id);
            }
        }
        $filtros = [];
        foreach ($usoColor as $aid => $vals) {
            $opciones = [];
            foreach ($vals as $valor => $color) {
                $opciones[] = ['valor' => $valor, 'color' => $color];
            }
            $filtros[] = [
                'id'       => $aid,
                'nombre'   => $usoNombre[$aid] ?? ('Atributo ' . $aid),
                'opciones' => $opciones,
            ];
        }

        return view('tienda.shop', [
            'categorias' => $categorias,
            'items'      => $items,
            'filtros'    => $filtros,
            'precioMin'  => $min,
            'precioMax'  => $max,
        ]);
    }
}
