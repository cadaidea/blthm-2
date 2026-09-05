<?php
namespace App\Http\Controllers;

use App\Models\Articulo;
use App\Models\BlogCategoria;
use App\Models\Categoria;
use App\Models\Etiqueta;
use App\Models\Pagina;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentSearchController extends Controller
{
    /**
     * Mapa de tipos buscables. Para agregar un modelo nuevo, solo se añade
     * una entrada aqui — no hay que tocar el resto del controlador.
     * 'browse' = closure que corre cuando el termino de busqueda esta vacio
     * (usuario solo escribio "/"): trae los primeros N sin filtrar.
     */
    protected function searchers(): array
    {
        return [
            'producto' => [
                'label' => 'Producto',
                'query' => fn (string $term) => Producto::query()
                    ->where('activo', true)->where('nombre', 'like', "%{$term}%")
                    ->orderBy('nombre')->limit(6)->get(['id', 'nombre', 'slug']),
                'browse' => fn () => Producto::query()
                    ->where('activo', true)->orderBy('nombre')->limit(6)->get(['id', 'nombre', 'slug']),
                'map' => fn (Producto $p) => [
                    'id' => $p->id, 'type' => 'producto', 'label' => 'Producto',
                    'name' => $p->nombre, 'url' => route('tienda.producto', $p->slug),
                ],
            ],
            'pagina' => [
                'label' => 'Página',
                'query' => fn (string $term) => Pagina::query()
                    ->where('activo', true)
                    ->where(fn ($q) => $q->where('titulo', 'like', "%{$term}%")->orWhere('slug', 'like', "%{$term}%"))
                    ->orderBy('titulo')->limit(6)->get(['id', 'titulo', 'slug']),
                'browse' => fn () => Pagina::query()
                    ->where('activo', true)->orderBy('titulo')->limit(6)->get(['id', 'titulo', 'slug']),
                'map' => fn (Pagina $p) => [
                    'id' => $p->id, 'type' => 'pagina', 'label' => 'Página',
                    'name' => $p->titulo, 'url' => route('paginas.show', $p->slug),
                ],
            ],
            'articulo' => [
                'label' => 'Artículo',
                'query' => fn (string $term) => Articulo::query()->with('categoria')
                    ->where('activo', true)->where('titulo', 'like', "%{$term}%")
                    ->orderBy('titulo')->limit(6)->get(['id', 'titulo', 'slug', 'blog_categoria_id']),
                'browse' => fn () => Articulo::query()->with('categoria')
                    ->where('activo', true)->orderByDesc('publicado_at')->limit(6)->get(['id', 'titulo', 'slug', 'blog_categoria_id']),
                'map' => function (Articulo $a) {
                    if (! $a->categoria) return null;
                    return [
                        'id' => $a->id, 'type' => 'articulo', 'label' => 'Artículo',
                        'name' => $a->titulo,
                        'url' => route('blog.articulo', ['categoria' => $a->categoria->slug, 'articulo' => $a->slug]),
                    ];
                },
            ],
            'categoria' => [
                'label' => 'Categoría (tienda)',
                'query' => fn (string $term) => Categoria::query()
                    ->where('activo', true)->where('nombre', 'like', "%{$term}%")
                    ->orderBy('nombre')->limit(6)->get(['id', 'nombre', 'slug']),
                'browse' => fn () => Categoria::query()
                    ->where('activo', true)->orderBy('nombre')->limit(6)->get(['id', 'nombre', 'slug']),
                'map' => fn (Categoria $c) => [
                    'id' => $c->id, 'type' => 'categoria', 'label' => 'Categoría (tienda)',
                    'name' => $c->nombre, 'url' => route('tienda.categoria', $c->slug),
                ],
            ],
            'blog_categoria' => [
                'label' => 'Categoría (blog)',
                'query' => fn (string $term) => BlogCategoria::query()
                    ->where('activo', true)->where('nombre', 'like', "%{$term}%")
                    ->orderBy('nombre')->limit(6)->get(['id', 'nombre', 'slug']),
                'browse' => fn () => BlogCategoria::query()
                    ->where('activo', true)->orderBy('nombre')->limit(6)->get(['id', 'nombre', 'slug']),
                'map' => fn (BlogCategoria $c) => [
                    'id' => $c->id, 'type' => 'blog_categoria', 'label' => 'Categoría (blog)',
                    'name' => $c->nombre, 'url' => route('blog.categoria', $c->slug),
                ],
            ],
            'etiqueta' => [
                'label' => 'Etiqueta',
                'query' => fn (string $term) => Etiqueta::query()
                    ->where('nombre', 'like', "%{$term}%")
                    ->orderBy('nombre')->limit(6)->get(['id', 'nombre', 'slug']),
                'browse' => fn () => Etiqueta::query()
                    ->orderBy('nombre')->limit(6)->get(['id', 'nombre', 'slug']),
                'map' => fn (Etiqueta $e) => [
                    'id' => $e->id, 'type' => 'etiqueta', 'label' => 'Etiqueta',
                    'name' => $e->nombre, 'url' => route('blog.etiqueta', $e->slug),
                ],
            ],
        ];
    }

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q'       => ['required', 'string', 'max:191'],
            'types'   => ['nullable', 'array'],
            'types.*' => ['string'],
        ]);

        // El termino puede venir como "/" solo (modo "listar todo") o con
        // texto real despues de la barra ("/tie" -> busca "tie").
        $raw = trim($validated['q']);
        $term = ltrim($raw, '/');
        $term = trim($term);
        $isBrowseMode = $raw !== '' && $term === '';

        if (! $isBrowseMode && mb_strlen($term) < 2) {
            return response()->json(['query' => $raw, 'results' => []]);
        }

        $searchers = $this->searchers();
        $requestedTypes = $validated['types'] ?? array_keys($searchers);
        $requestedTypes = array_values(array_intersect($requestedTypes, array_keys($searchers)));

        $results = [];
        foreach ($requestedTypes as $type) {
            $config = $searchers[$type];
            $items = $isBrowseMode ? ($config['browse'])() : ($config['query'])($term);
            foreach ($items as $item) {
                $mapped = ($config['map'])($item);
                if ($mapped) $results[] = $mapped;
            }
        }

        return response()->json([
            'query'   => $raw,
            'results' => $results,
        ]);
    }
}
