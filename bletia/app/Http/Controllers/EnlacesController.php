<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnlacesController extends Controller
{
    public function index(Request $r)
    {
        $q = trim((string) $r->get('q', ''));
        if (mb_strlen($q) < 2) return response()->json([]);
        $like = '%' . $q . '%';
        $out = [];

        $push = function ($items) use (&$out) { foreach ($items as $i) $out[] = $i; };

        // Productos
        try {
            $push(\App\Models\Producto::query()
                ->where('activo', true)
                ->where('nombre', 'like', $like)->limit(6)->get()
                ->map(fn ($p) => ['tipo' => 'Producto', 'label' => $p->nombre, 'url' => route('tienda.producto', $p->slug)])->all());
        } catch (\Throwable $e) {}

        // Categorías
        try {
            $push(\App\Models\Categoria::query()
                ->where('activo', true)
                ->where('nombre', 'like', $like)->limit(6)->get()
                ->map(fn ($c) => ['tipo' => 'Categoría', 'label' => $c->nombre, 'url' => route('tienda.categoria', $c->slug)])->all());
        } catch (\Throwable $e) {}

        // Artículos (blog)
        try {
            $push(\App\Models\Articulo::query()
                ->publicado()
                ->where('titulo', 'like', $like)->limit(6)->get()
                ->map(fn ($a) => ['tipo' => 'Blog', 'label' => $a->titulo, 'url' => $a->url])->all());
        } catch (\Throwable $e) {}

        // Páginas
        try {
            $push(\App\Models\Pagina::query()
                ->where('activo', true)
                ->where(fn ($w) => $w->where('titulo', 'like', $like)->orWhere('slug', 'like', $like))
                ->limit(6)->get()
                ->map(fn ($p) => ['tipo' => 'Página', 'label' => $p->titulo ?? $p->slug, 'url' => url('/' . ltrim($p->slug, '/'))])->all());
        } catch (\Throwable $e) {}

        return response()->json(array_slice($out, 0, 20));
    }
}
