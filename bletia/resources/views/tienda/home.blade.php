@extends('tienda.layout')

@php
    $marca = \App\Models\Ajuste::get('marca', config('tienda.marca'));
    $eslogan = \App\Models\Ajuste::get('eslogan', config('tienda.eslogan'));
    $heroImg = \App\Models\Ajuste::get('home_hero_img');
    $heroImgUrl = $heroImg ? \Illuminate\Support\Facades\Storage::disk('public')->url($heroImg) : null;
    $heroTitulo = \App\Models\Ajuste::get('home_hero_titulo') ?: 'Piezas que definen un espacio';
    $heroTexto  = \App\Models\Ajuste::get('home_hero_texto') ?: 'Mobiliario y decoración de autor, seleccionados a mano.';
    $heroCta    = \App\Models\Ajuste::get('home_hero_cta') ?: 'Ver colección';
    $heroCtaUrl = \App\Models\Ajuste::get('home_hero_cta_url') ?: ($categorias->first() ? route('tienda.categoria', $categorias->first()->slug) : '#');
    $introTit   = \App\Models\Ajuste::get('home_intro_titulo') ?: 'Exclusividad en cada detalle';
    $introTxt   = \App\Models\Ajuste::get('home_intro_texto') ?: 'Trabajamos con materiales nobles y series cortas. Cada pieza llega lista para perdurar y elevar tu espacio.';
    $homeBloques = json_decode(\App\Models\Ajuste::get('home_bloques') ?: '[]', true) ?: [];
@endphp

@section('title', $marca . ($eslogan ? ' · ' . $eslogan : ''))

@section('content')
<section class="t-hero">
    <div class="t-hero-bg">@if($heroImgUrl)<img src="{{ $heroImgUrl }}" width="1920" height="1080" fetchpriority="high" alt="{{ $heroTitulo }}">@endif</div>
    <div class="t-hero-in">
        <p class="t-eyebrow" style="color:#fff;opacity:.85">{{ $eslogan ?: $marca }}</p>
        <h1>{{ $heroTitulo }}</h1>
        <p>{{ $heroTexto }}</p>
        <a href="{{ $heroCtaUrl }}" class="t-btn t-btn--onimg">{{ $heroCta }}</a>
    </div>
</section>

<div class="t-intro">
    <h2>{{ $introTit }}</h2>
    <p>{{ $introTxt }}</p>
</div>

@if(!empty($homeBloques))
<section class="t-wrap t-section t-home-bloques">
    @include('tienda.partials.bloques', ['bloques' => $homeBloques])
</section>
@endif

@if($categorias->count())
<section class="t-wrap t-section">
    <div class="t-section-head"><h2 class="t-h2" style="margin:0">Colecciones</h2></div>
    <div class="t-colecciones">
        @foreach($categorias as $c)
            <a class="t-col-card" href="{{ route('tienda.categoria', $c->slug) }}">
                @if($c->imagen ?? false)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($c->imagen) }}" width="600" height="600" loading="lazy" alt="{{ $c->nombre }}">@endif
                <span>{{ $c->nombre }}</span>
            </a>
        @endforeach
    </div>
</section>
@endif

@if($novedades->count())
<section class="t-wrap t-section">
    <div class="t-section-head">
        <h2 class="t-h2" style="margin:0">Novedades</h2>
        @if($categorias->first())<a class="t-btn" href="{{ route('tienda.categoria', $categorias->first()->slug) }}">Ver todo</a>@endif
    </div>
    <div class="t-grid">
        @foreach($novedades as $producto)
            @include('tienda.partials.producto-card', ['producto' => $producto])
        @endforeach
    </div>
</section>
@endif

@if($feature)
<section class="t-wrap t-section">
    <div class="t-feature">
        <div class="t-feature-img">
            @if($feature->imagen_principal)<img src="{{ $feature->imagen_principal }}" width="900" height="900" loading="lazy" alt="{{ $feature->nombre }}">@endif
        </div>
        <div class="t-feature-txt">
            <p class="t-eyebrow">Destacado</p>
            <h2 class="t-h2">{{ $feature->nombre }}</h2>
            @if($feature->descripcion_corta)<p class="t-lead">{{ $feature->descripcion_corta }}</p>@endif
            <p class="t-feature-price">${{ number_format($feature->precio, 2) }}</p>
            <p class="t-iva">Incluido IVA</p>
            <div style="margin-top:18px"><a class="t-btn t-btn--footer" href="{{ route('tienda.producto', $feature->slug) }}">Descubrir</a></div>
        </div>
    </div>
</section>
@endif
@include("tienda.partials.home-posts", ["posts" => $posts ?? collect()])
@endsection
