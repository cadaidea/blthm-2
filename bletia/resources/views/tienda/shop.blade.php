@extends('tienda.layout')
@section('title', 'Tienda · ' . config('tienda.marca'))
@section('meta_description', 'Todos los productos de ' . config('tienda.marca') . '. Filtra por categoría, tapiz, madera y precio.')
@section('canonical', url('/shop'))
@section('content')
<div class="t-shop">
    <h1 class="t-h1">Todos los productos</h1>

    {{-- Tabs de categoria --}}
    <div class="t-shop-tabs" id="shopTabs">
        <button class="t-tab is-active" data-cat="all">Todos</button>
        @foreach($categorias as $c)
            <button class="t-tab" data-cat="{{ $c->id }}">{{ $c->nombre }}</button>
        @endforeach
    </div>

    <div class="t-shop-bar">
        <input type="search" id="shopSearch" class="t-shop-search" placeholder="Buscar...">
        <div class="t-shop-sort">
            <label>Ordenar por:</label>
            <select id="shopSort">
                <option value="destacado">Destacado</option>
                <option value="precio_asc">Precio: menor a mayor</option>
                <option value="precio_desc">Precio: mayor a menor</option>
                <option value="nombre">Nombre</option>
            </select>
        </div>
        <button type="button" class="t-shop-filtros-btn" id="shopFiltrosBtn">Filtros</button>
    </div>

    <div class="t-shop-layout">
        {{-- Panel de filtros --}}
        <aside class="t-shop-aside" id="shopAside">
            <div class="t-aside-head">
                <span>Filtros</span>
                <button type="button" id="shopAsideClose" aria-label="Cerrar">&times;</button>
            </div>

            @foreach($filtros as $f)
            <div class="t-filtro">
                <h4>{{ $f['nombre'] }}</h4>
                <div class="t-swatches" data-attr="{{ $f['id'] }}">
                    @foreach($f['opciones'] as $op)
                        @php($tieneColor = !empty($op['color']))
                        <button type="button" class="t-swatch {{ $tieneColor ? '' : 't-swatch--txt' }}"
                                data-attr="{{ $f['id'] }}" data-valor="{{ $op['valor'] }}"
                                title="{{ $op['valor'] }}"
                                @if($tieneColor) style="background:{{ $op['color'] }}" @endif>
                            @if(!$tieneColor){{ $op['valor'] }}@endif
                        </button>
                    @endforeach
                </div>
            </div>
            @endforeach

            <div class="t-filtro">
                <h4>Rango de precio</h4>
                <div class="t-precio">
                    <div class="t-precio-vals"><span id="pvMin">${{ number_format($precioMin) }}</span><span id="pvMax">${{ number_format($precioMax) }}</span></div>
                    <div class="t-range">
                        <input type="range" id="rMin" min="{{ $precioMin }}" max="{{ $precioMax }}" value="{{ $precioMin }}">
                        <input type="range" id="rMax" min="{{ $precioMin }}" max="{{ $precioMax }}" value="{{ $precioMax }}">
                    </div>
                </div>
            </div>

            <button type="button" class="t-shop-reset" id="shopReset">Limpiar filtros</button>
        </aside>

        {{-- Grilla de productos --}}
        <div class="t-shop-main">
            <p class="t-shop-count"><span id="shopCount">{{ count($items) }}</span> productos</p>
            <section class="t-grid" id="shopGrid">
                @foreach($items as $it)
                    @php($p = $it['producto'])
                    @php($tapiz = implode('|', $it['opciones'][1] ?? []))
                    @php($lado = implode('|', $it['opciones'][2] ?? []))
                    @php($madera = implode('|', $it['opciones'][3] ?? []))
                    <div class="t-shop-item"
                         data-cat="{{ $it['cat'] }}"
                         data-precio="{{ $it['precio'] }}"
                         data-nombre="{{ \Illuminate\Support\Str::lower($p->nombre) }}"
                         data-destacado="{{ $it['destacado'] ? 1 : 0 }}"
                         data-attr-1="{{ $tapiz }}"
                         data-attr-2="{{ $lado }}"
                         data-attr-3="{{ $madera }}">
                        @include('tienda.partials.producto-card', ['producto' => $p])
                    </div>
                @endforeach
            </section>
            <p class="t-shop-vacio" id="shopVacio" style="display:none">No hay productos que coincidan con los filtros.</p>
        </div>
    </div>
</div>
@php($shopJs = public_path('js/shop.js'))
<script src="{{ asset('js/shop.js') }}?v={{ is_file($shopJs) ? filemtime($shopJs) : '1' }}"></script>
@endsection
