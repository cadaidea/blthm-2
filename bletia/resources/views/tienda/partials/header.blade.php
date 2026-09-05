@php
    use App\Models\Categoria;
    use App\Models\BlogCategoria;
    use App\Models\Pagina;
    use Illuminate\Support\Facades\Storage;

    $tipo = $tipo ?? 'tienda';
    $hdata = $hdata ?? [];
    $logo = \App\Models\Ajuste::get('logo');
    $logoClaro = \App\Models\Ajuste::get('logo_claro') ?: $logo;
    $logoUrl = $logo ? Storage::disk('public')->url($logo) : null;
    $logoClaroUrl = $logoClaro ? Storage::disk('public')->url($logoClaro) : null;
    $logueado = session('cliente_id');

    // Menú central + menú móvil según el tipo de header
    $menu = collect();
    if ($tipo === 'paginas') {
        $menu = Pagina::where('activo', true)->where('mostrar_en_menu', true)->orderBy('orden')->get()
            ->map(fn ($p) => ['nombre' => $p->titulo, 'url' => route('paginas.show', $p->slug)]);
    } elseif ($tipo === 'blog' || $tipo === 'articulo') {
        $menu = BlogCategoria::where('activo', true)->orderBy('orden')->get()
            ->map(fn ($c) => ['nombre' => $c->nombre, 'url' => route('blog.categoria', $c->slug)]);
    } else {
        $menu = Categoria::where('activo', true)->orderBy('orden')->take(8)->get()
            ->map(fn ($c) => ['nombre' => $c->nombre, 'url' => route('tienda.categoria', $c->slug)]);
    }
@endphp
<header class="t-hd {{ $esHome ? 't-hd--home' : 't-hd--solid' }}" id="t-hd" @if($esHome) data-home="1" @endif>
    <div class="t-wrap t-hd-in">
        {{-- IZQ: logo --}}
        <a href="{{ route('tienda.home') }}" class="t-hd-logo" aria-label="{{ config('tienda.marca') }}">
            @if($logoUrl)
                <img class="t-logo-solid" src="{{ $logoUrl }}" alt="{{ config('tienda.marca') }}">
                <img class="t-logo-claro" src="{{ $logoClaroUrl }}" alt="{{ config('tienda.marca') }}">
            @else
                <span class="t-brand">{{ config('tienda.marca') }}</span>
            @endif
        </a>

        {{-- CEN --}}
        @if($tipo === 'articulo')
            <div class="t-hd-art">
                @if(!empty($hdata['cat']))
                    <a href="{{ route('blog.categoria', $hdata['cat']->slug) }}" class="t-hd-art-cat">{{ $hdata['cat']->nombre }}</a>
                    <span class="t-hd-art-sep">|</span>
                @endif
                <span class="t-hd-art-tit">{{ $hdata['titulo'] ?? '' }}</span>
            </div>
        @else
            <nav class="t-hd-nav">
                @foreach($menu as $m)
                    <a href="{{ $m['url'] }}">{{ $m['nombre'] }}</a>
                @endforeach
            </nav>
        @endif

        {{-- DER: íconos --}}
        <div class="t-hd-icons">
            <button class="t-ic" id="t-open-search" aria-label="Buscar">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
            </button>
            <a class="t-ic" id="t-wish-header-link" href="{{ route('cuenta.guardados') }}" aria-label="Guardados">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21l7.8-7.5 1-1.1a5.5 5.5 0 0 0 0-7.8z"/></svg>
                @php($nGuardados = session('cliente_id') ? \Illuminate\Support\Facades\DB::table('guardados')->where('cliente_id', session('cliente_id'))->count() : 0)
                <span class="t-cart-badge" id="t-wish-header-badge" @if($nGuardados == 0) style="display:none" @endif>{{ $nGuardados }}</span>
            </a>
            @if($logueado)
                <a class="t-ic" href="{{ route('cuenta.panel') }}" aria-label="Mi cuenta">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>
                </a>
            @else
                <button class="t-ic" id="t-open-auth" aria-label="Ingresar">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>
                </button>
            @endif
            @if($tipo === 'tienda')
                <a class="t-ic t-ic-cart" href="{{ route('carrito.ver') }}" aria-label="Carrito">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.5 13h11l2-9H6"/></svg>
                    @php($n = \App\Support\Cart::cantidadItems())
                    @if($n > 0)<span class="t-cart-badge">{{ $n }}</span>@endif
                </a>
            @endif
            <button class="t-ic t-hd-burger" id="t-open-menu" aria-label="Menú">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
            </button>
        </div>
    </div>

</header>

{{-- Menú móvil (popup pantalla completa) --}}
<div class="t-drawer" id="t-drawer" aria-hidden="true">
    <div class="t-drawer-top">
        <a href="{{ route('tienda.home') }}" class="t-brand">{{ config('tienda.marca') }}</a>
        <button class="t-drawer-close" id="t-close-menu" aria-label="Cerrar">
            <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </div>
    <nav class="t-drawer-nav">
        @foreach($menu as $m)
            <a href="{{ $m['url'] }}">{{ $m['nombre'] }}</a>
        @endforeach
    </nav>
</div>
