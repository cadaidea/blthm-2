@extends('tienda.layout')

@php($img = $producto->imagen_principal)

@section('title', ($producto->meta_title ?: $producto->nombre) . ' · ' . config('tienda.marca'))
@section('meta_description', $producto->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($producto->descripcion_corta ?: $producto->descripcion), 180))
@section('canonical', route('tienda.producto', $producto->slug))
@section('og_type', 'product')
@if($img)
@section('og_image', $img)
@endif

@push('jsonld')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org', '@type' => 'Product',
    'name' => $producto->nombre, 'sku' => $producto->sku,
    'description' => \Illuminate\Support\Str::limit(strip_tags($producto->descripcion_corta ?: $producto->descripcion), 300),
    'image' => $producto->imagenes->pluck('url')->all(),
    'brand' => ['@type' => 'Brand', 'name' => config('tienda.marca')],
    'offers' => [
        '@type' => 'Offer', 'price' => number_format($producto->precio, 2, '.', ''),
        'priceCurrency' => config('tienda.moneda'),
        'availability' => $producto->comprable ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        'priceValidUntil' => now()->endOfYear()->addYear()->format('Y-m-d'),
        'url' => route('tienda.producto', $producto->slug),
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

@section('content')
@php($shareU = urlencode(route('tienda.producto', $producto->slug)))
@php($shareT = urlencode($producto->nombre))
@php($vs = $producto->variantes)
@php($oids = $vs->flatMap(fn ($v) => array_values((array) ($v->opciones ?: [])))->map(fn ($x) => (int) $x)->filter()->unique()->values())
@php($opMap = $oids->isNotEmpty() ? \App\Models\AtributoOpcion::with('atributo')->whereIn('id', $oids)->get()->keyBy('id') : collect())
@php($aids = $vs->flatMap(fn ($v) => collect((array) ($v->opciones ?: []))->filter(fn ($x) => filled($x))->keys())->map(fn ($x) => (int) $x)->unique()->values())
@php($vdata = $vs->map(fn ($v) => ['id' => $v->id, 'pvp' => $v->pvp_final, 'foto' => $v->foto_url, 'op' => collect((array) ($v->opciones ?: []))->filter(fn ($x) => filled($x))->mapWithKeys(fn ($oid, $aid) => [(string) (int) $aid => (int) $oid])])->values())
@php($precioBase = $vs->count() ? (float) $vs->map->pvp_final->min() : (float) $producto->precio)

<nav class="t-bc">
    <a href="{{ route('tienda.home') }}">Inicio</a>
    @if($producto->categoria) / <a href="{{ route('tienda.categoria', $producto->categoria->slug) }}">{{ $producto->categoria->nombre }}</a>@endif
    / <span>{{ $producto->nombre }}</span>
</nav>

<div class="t-prod">
    <div class="t-prod-gal">
        @if($producto->imagenes->count())
            <div class="t-prod-main-wrap" id="t-prod-main-wrap" data-zoom="{{ $producto->imagenes->first()->url }}">
                <img class="t-prod-main" id="t-prod-main" src="{{ $producto->imagenes->first()->url }}" alt="{{ $producto->imagenes->first()->alt ?: $producto->nombre }}">
            </div>
            @if($producto->imagenes->count() > 1)
                <div class="t-prod-thumbs">
                    @foreach($producto->imagenes as $im)
                        <img src="{{ $im->url }}" alt="{{ $im->alt ?: $producto->nombre }}" loading="lazy" data-zoom="{{ $im->url }}">
                    @endforeach
                </div>
            @endif
        @else
            <div class="t-prod-main-wrap"><div class="t-card-noimg">Sin imagen</div></div>
        @endif
    </div>

    <div class="t-prod-info">
        @if($producto->categoria)<a href="{{ route('tienda.categoria', $producto->categoria->slug) }}" class="t-eyebrow t-prod-cat">{{ $producto->categoria->nombre }}</a>@endif
        <div class="t-prod-head">
            <h1 class="t-h1">{{ $producto->nombre }}</h1>
            <div class="t-share t-prod-share">
                @include('tienda.partials.wish-btn', ['producto' => $producto])
                <button class="t-ic" id="t-share-btn" aria-label="Compartir">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.6 13.5l6.8 4M15.4 6.5l-6.8 4"/></svg>
                </button>
                <div class="t-share-pop" id="t-share-pop">
                    <a href="https://wa.me/?text={{ $shareT }}%20{{ $shareU }}" target="_blank" rel="noopener">WhatsApp</a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareU }}" target="_blank" rel="noopener">Facebook</a>
                    <a href="https://twitter.com/intent/tweet?url={{ $shareU }}&text={{ $shareT }}" target="_blank" rel="noopener">X (Twitter)</a>
                    <button type="button" id="t-copy-link">Copiar enlace</button>
                </div>
            </div>
        </div>

        <p class="t-prod-price" id="t-prod-price">${{ number_format($precioBase, 2) }}</p>
        <p class="t-iva" id="t-prod-iva">Incluido IVA ({{ rtrim(rtrim(number_format($producto->iva_rate,2),'0'),'.') }}%): ${{ number_format($precioBase - $precioBase/(1+$producto->iva_rate/100), 2) }}</p>
        @if($producto->sku)<p class="t-prod-sku">SKU: {{ $producto->sku }}</p>@endif
        @if($producto->descripcion_corta)<p class="t-lead">{{ $producto->descripcion_corta }}</p>@endif

        <div class="t-avail">
            @if($producto->stock_total > 0)
                <span class="t-avail-badge t-avail-ok">En stock</span>
                <span class="t-avail-txt">Disponible · {{ $producto->stock_total }} {{ $producto->stock_total == 1 ? 'unidad' : 'unidades' }}</span>
            @elseif($producto->bajo_pedido)
                <span class="t-avail-badge t-avail-mto">Made to Order</span>
                <span class="t-avail-txt">{{ $producto->mto_texto_final }}</span>
            @endif
        </div>

        @if($producto->comprable)
            <form method="post" action="{{ route('carrito.agregar', $producto->slug) }}" id="t-prod-form"
                  data-iva="{{ $producto->iva_rate }}" data-nattrs="{{ $aids->count() }}" data-base="{{ number_format($precioBase, 2, '.', '') }}">
                @csrf
                <input type="hidden" name="variante_id" id="t-variante-id" value="">
                <script id="t-vdata" type="application/json">{!! json_encode($vdata, JSON_UNESCAPED_SLASHES) !!}</script>

                @foreach($aids as $aid)
                    @php($atr = optional($opMap->first(fn ($o) => (int) $o->atributo_id === (int) $aid))->atributo)
                    @php($ops = $opMap->filter(fn ($o) => (int) $o->atributo_id === (int) $aid)->unique('id')->values())
                    @php($tipo = $atr?->tipo ?? 'texto')
                    <div class="t-sw-group" data-attr="{{ $aid }}">
                        <div class="t-sw-head">
                            <span class="t-sw-name">{{ $atr?->nombre ?? 'Opción' }}</span>
                            <span class="t-sw-sel" id="sel-{{ $aid }}"></span>
                        </div>
                        @if($tipo === 'texto')
                            <select class="t-sw-select" data-attr="{{ $aid }}">
                                <option value="">Elegir…</option>
                                @foreach($ops as $o)
                                    <option value="{{ $o->id }}">{{ $o->valor }}</option>
                                @endforeach
                            </select>
                        @else
                            <div class="t-sw-row {{ $tipo === 'color' ? 't-sw-row--color' : 't-sw-row--img' }}">
                                @foreach($ops as $o)
                                    <button type="button" class="t-sw {{ $tipo === 'color' ? 't-sw--color' : 't-sw--img' }}" data-attr="{{ $aid }}" data-opt="{{ $o->id }}" data-valor="{{ $o->valor }}" title="{{ $o->valor }}" @if($o->foto_url) data-foto="{{ $o->foto_url }}" @endif>
                                        @if($o->foto_url)
                                            <img src="{{ $o->foto_url }}" alt="{{ $o->valor }}">
                                        @elseif($o->color)
                                            <span class="t-sw-dot" style="background:{{ $o->color }}"></span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach

                @if($aids->count())<p class="t-sw-hint" id="t-sw-hint">Selecciona las opciones para ver el precio.</p>@endif

                <div class="t-add-form">
                    <input type="number" name="cantidad" value="1" min="1" class="t-qty">
                    <button type="submit" class="t-btn t-btn--cta" id="t-buy-btn">Comprar</button>
                </div>
            </form>
        @else
            <p><button class="t-btn" disabled>Agotado</button></p>
        @endif

        <details class="t-acc" open>
            <summary>Detalles del producto</summary>
            <div class="t-prod-desc">
                {!! $producto->descripcion !!}
            </div>
        </details>
    </div>
</div>

@if($relacionados->count())
<section class="t-section t-related">
    <h2 class="t-h2">También te puede gustar</h2>
    <div class="t-grid">
        @foreach($relacionados as $producto)
            @include('tienda.partials.producto-card', ['producto' => $producto])
        @endforeach
    </div>
</section>
@endif
@endsection
