<?php

namespace App\Http\Controllers;

use App\Models\Articulo;
use App\Models\BlogCategoria;
use App\Models\Categoria;
use App\Models\Empleado;
use App\Models\Etiqueta;
use App\Models\Pagina;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TiendaController extends Controller
{
    public function robots()
    {
        $lines = ["User-agent: *", "Disallow:", ""];
        $ai = \App\Models\Ajuste::get('ai_bots', '1');
        if ($ai === '0' || $ai === 0 || $ai === false) {
            foreach (['GPTBot','ChatGPT-User','ClaudeBot','anthropic-ai','PerplexityBot','Google-Extended','CCBot','Bytespider','Amazonbot'] as $bot) {
                $lines[] = "User-agent: {$bot}";
                $lines[] = "Disallow: /";
                $lines[] = "";
            }
        }
        $lines[] = "Sitemap: " . url('/sitemap.xml');
        return response(implode("\n", $lines) . "\n", 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function home()
    {
        $categorias = Categoria::where('activo', true)->orderBy('orden')->get();
        $novedades  = Producto::where('activo', true)->with('imagenes')->latest()->take(8)->get();
        $featureId  = \App\Models\Ajuste::get('home_producto_id');
        $feature    = $featureId ? Producto::with('imagenes')->find($featureId) : null;
        if (! $feature) {
            $feature = Producto::where('activo', true)->where('destacado', true)->with('imagenes')->latest()->first();
        }
        $posts = Articulo::publicado()->with("categoria")->orderByDesc("publicado_at")->orderByDesc("id")->take(6)->get();
        return view('tienda.home', compact('categorias', 'novedades', 'feature', 'posts'));
    }

    public function categoria(Categoria $categoria)
    {
        abort_unless($categoria->activo, 404);
        $productos = $categoria->productos()->where('activo', true)->with(['imagenes', 'variantes'])->latest()->paginate(12);
        return view('tienda.categoria', compact('categoria', 'productos'));
    }

    public function producto(Producto $producto)
    {
        abort_unless($producto->activo, 404);
        $producto->load(['imagenes', 'variantes', 'categoria']);
        $relacionados = Producto::where('activo', true)->where('categoria_id', $producto->categoria_id)
            ->where('id', '!=', $producto->id)->with('imagenes')->take(4)->get();
        return view('tienda.producto', compact('producto', 'relacionados'));
    }

    public function buscar(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $r = $this->buscarTodo($q, 30);
        return view('tienda.buscar', ['q' => $q] + $r);
    }

    public function buscarApi(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(new \stdClass());
        }
        $r = $this->buscarTodo($q, 4);

        $out = [
            'productos' => $r['productos']->map(fn ($p) => [
                'nombre' => $p->nombre,
                'precio' => number_format($p->precio_con_iva, 2),
                'url'    => route('tienda.producto', $p->slug),
                'img'    => $p->imagen_principal,
            ])->values(),
            'categorias' => $r['categorias']->map(fn ($c) => [
                'nombre' => $c->nombre, 'url' => route('tienda.categoria', $c->slug),
            ])->values(),
            'paginas' => $r['paginas']->map(fn ($p) => [
                'nombre' => $p->titulo, 'url' => url('/' . $p->slug),
            ])->values(),
            'articulos' => $r['articulos']->map(fn ($a) => [
                'nombre' => $a->titulo,
                'url'    => $a->categoria ? route('blog.articulo', [$a->categoria->slug, $a->slug]) : route('blog.index'),
            ])->values(),
            'blog_categorias' => $r['blog_categorias']->map(fn ($c) => [
                'nombre' => $c->nombre, 'url' => route('blog.categoria', $c->slug),
            ])->values(),
            'etiquetas' => $r['etiquetas']->map(fn ($e) => [
                'nombre' => $e->nombre, 'url' => route('blog.etiqueta', $e->slug),
            ])->values(),
            'autores' => $r['autores']->map(fn ($e) => [
                'nombre' => $e->nombre, 'url' => route('blog.autor', $e->slug),
            ])->values(),
        ];
        return response()->json($out);
    }

    protected function buscarTodo(string $q, int $limite = 12): array
    {
        if ($q === '') {
            return [
                'productos' => collect(), 'categorias' => collect(), 'paginas' => collect(),
                'articulos' => collect(), 'blog_categorias' => collect(),
                'etiquetas' => collect(), 'autores' => collect(),
            ];
        }
        $like = "%{$q}%";

        $productos = Producto::where('activo', true)
            ->where(fn ($w) => $w->where('nombre', 'like', $like)->orWhere('sku', 'like', $like)->orWhere('descripcion_corta', 'like', $like))
            ->with('imagenes')->take($limite)->get();

        $categorias = Categoria::where('activo', true)
            ->where(fn ($w) => $w->where('nombre', 'like', $like)->orWhere('slug', 'like', $like))
            ->take($limite)->get();

        $paginas = Pagina::where('activo', true)
            ->where(fn ($w) => $w->where('titulo', 'like', $like)->orWhere('slug', 'like', $like))
            ->take($limite)->get();

        $articulos = Articulo::where('activo', true)
            ->where(fn ($w) => $w->where('titulo', 'like', $like)->orWhere('extracto', 'like', $like)->orWhere('slug', 'like', $like))
            ->with('categoria')->take($limite)->get();

        $blogCats = BlogCategoria::where('activo', true)
            ->where(fn ($w) => $w->where('nombre', 'like', $like)->orWhere('slug', 'like', $like))
            ->take($limite)->get();

        $etiquetas = Etiqueta::where(fn ($w) => $w->where('nombre', 'like', $like)->orWhere('slug', 'like', $like))
            ->take($limite)->get();

        $autores = Empleado::whereNotNull('slug')->where(fn ($w) => $w->where('nombre', 'like', $like)->orWhere('slug', 'like', $like))
            ->take($limite)->get();

        return [
            'productos' => $productos, 'categorias' => $categorias, 'paginas' => $paginas,
            'articulos' => $articulos, 'blog_categorias' => $blogCats,
            'etiquetas' => $etiquetas, 'autores' => $autores,
        ];
    }

    public function sitemap(): Response
    {
        $productos  = Producto::where('activo', true)->get(['slug', 'updated_at']);
        $categorias = Categoria::where('activo', true)->get(['slug', 'updated_at']);
        $xml  = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $xml .= '<url><loc>' . url('/') . '</loc></url>';
        foreach ($categorias as $c) {
            $xml .= '<url><loc>' . route('tienda.categoria', $c->slug) . '</loc><lastmod>' . $c->updated_at->toAtomString() . '</lastmod></url>';
        }
        foreach ($productos as $p) {
            $xml .= '<url><loc>' . route('tienda.producto', $p->slug) . '</loc><lastmod>' . $p->updated_at->toAtomString() . '</lastmod></url>';
        }
        $xml .= '</urlset>';
        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
