<?php
namespace App\Http\Controllers;
use App\Models\Articulo;
use App\Models\BlogCategoria;
use App\Models\PostSlug;
class BlogController extends Controller
{
    public function index()
    {
        $categorias = BlogCategoria::where('activo', true)->orderBy('orden')->get();
        $articulos  = Articulo::publicado()->with('categoria')->latest('publicado_at')->paginate(9);
        return view('blog.index', compact('categorias', 'articulos') + ['headerTipo' => 'blog']);
    }
    public function categoria(BlogCategoria $blogCategoria)
    {
        abort_unless($blogCategoria->activo, 404);
        $categorias = BlogCategoria::where('activo', true)->orderBy('orden')->get();
        $articulos  = $blogCategoria->articulos()->publicado()->with('categoria')->latest('publicado_at')->paginate(9);
        return view('blog.categoria', compact('blogCategoria', 'categorias', 'articulos') + ['headerTipo' => 'blog']);
    }
    public function articulo(string $categoria, string $slug)
    {
        // Buscar por slug actual
        $articulo = Articulo::where('slug', $slug)->first();

        // Si no existe, buscar en slugs anteriores
        if (! $articulo) {
            $anterior = PostSlug::with('articulo')->where('slug', $slug)->first();
            if ($anterior && $anterior->articulo) {
                $art = $anterior->articulo;
                $cat = $art->categoria?->slug ?: 'articulos';
                return redirect()->route('blog.articulo', [$cat, $art->slug], 301);
            }
            abort(404);
        }

        abort_unless($articulo->activo, 404);
        $articulo->load('categoria', 'editor', 'etiquetas');
        $catSlug = $articulo->categoria?->slug ?: 'articulos';
        if ($categoria !== $catSlug) {
            return redirect()->route('blog.articulo', [$catSlug, $articulo->slug], 301);
        }
        $relacionados = Articulo::publicado()->where('id', '!=', $articulo->id)
            ->when($articulo->blog_categoria_id, fn ($q) => $q->where('blog_categoria_id', $articulo->blog_categoria_id))
            ->latest('publicado_at')->take(3)->get();
        return view('blog.articulo', compact('articulo', 'relacionados') + ['headerTipo' => 'articulo', 'headerData' => ['cat' => $articulo->categoria, 'titulo' => $articulo->titulo]]);
    }
    public function etiqueta(\App\Models\Etiqueta $etiqueta)
    {
        $categorias = BlogCategoria::where('activo', true)->orderBy('orden')->get();
        $articulos  = $etiqueta->articulos()->publicado()->with('categoria')->latest('publicado_at')->paginate(9);
        $titulo = '#' . $etiqueta->nombre;
        return view('blog.listado', compact('categorias', 'articulos', 'titulo') + ['headerTipo' => 'blog']);
    }
    public function autor(\App\Models\Empleado $editor)
    {
        $categorias = BlogCategoria::where('activo', true)->orderBy('orden')->get();
        $articulos  = $editor->articulos()->publicado()->with('categoria')->latest('publicado_at')->paginate(9);
        $titulo = $editor->nombre;
        return view('blog.listado', compact('categorias', 'articulos', 'titulo', 'editor') + ['headerTipo' => 'blog']);
    }

    public function feed()
    {
        $articulos = \App\Models\Articulo::publicado()->with('categoria')
            ->latest('publicado_at')->take(30)->get();

        $marca = \App\Models\Ajuste::get('marca', 'Bletia');
        $desc  = \App\Models\Ajuste::get('meta_home', \App\Models\Ajuste::get('eslogan', ''));
        $now   = now()->toRssString();

        $items = '';
        foreach ($articulos as $a) {
            $items .= '<item>'
                . '<title>' . htmlspecialchars($a->titulo, ENT_XML1) . '</title>'
                . '<link>' . $a->url . '</link>'
                . '<guid isPermaLink="true">' . $a->url . '</guid>'
                . '<pubDate>' . optional($a->publicado_at)->toRssString() . '</pubDate>'
                . ($a->categoria ? '<category>' . htmlspecialchars($a->categoria->nombre, ENT_XML1) . '</category>' : '')
                . '<description>' . htmlspecialchars($a->extracto ?: strip_tags((string) $a->meta_description), ENT_XML1) . '</description>'
                . '</item>';
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">'
            . '<channel>'
            . '<title>' . htmlspecialchars($marca, ENT_XML1) . ' · Blog</title>'
            . '<link>' . route('blog.index') . '</link>'
            . '<description>' . htmlspecialchars((string) $desc, ENT_XML1) . '</description>'
            . '<language>es-EC</language>'
            . '<lastBuildDate>' . $now . '</lastBuildDate>'
            . '<atom:link href="' . url('/feed.xml') . '" rel="self" type="application/rss+xml"/>'
            . $items
            . '</channel></rss>';

        return response($xml, 200)->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }
}
