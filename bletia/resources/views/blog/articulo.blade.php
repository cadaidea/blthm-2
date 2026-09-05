@extends('tienda.layout')
@section('title', ($articulo->meta_title ?: $articulo->titulo) . ' · ' . config('tienda.marca'))
@section('meta_description', $articulo->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($articulo->extracto ?: $articulo->contenido), 180))
@section('canonical', $articulo->url)
@section('og_type', 'article')
@if($articulo->imagen_url)@section('og_image', $articulo->imagen_url)@endif
@php($cover = $articulo->imagen_cabecera && $articulo->imagen_url)
@section('body_class', $cover ? 't-article-hero' : '')

@push('jsonld')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org', '@type' => 'Article',
    'headline' => $articulo->titulo,
    'description' => \Illuminate\Support\Str::limit(strip_tags($articulo->meta_description ?: $articulo->extracto ?: $articulo->contenido), 200),
    'image' => array_filter([$articulo->imagen_url]),
    'datePublished' => optional($articulo->publicado_at)->toAtomString(),
    'dateModified' => $articulo->updated_at->toAtomString(),
    'author' => ['@type' => 'Person', 'name' => $articulo->autor ?: config('tienda.marca')],
    'publisher' => ['@type' => 'Organization', 'name' => config('tienda.marca')],
    'mainEntityOfPage' => $articulo->url,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

@section('content')
@php($share = urlencode($articulo->url))
@php($shareT = urlencode($articulo->titulo))

@if($cover)
<header class="t-art-hero" style="background-image:linear-gradient(rgba(0,0,0,.12),rgba(0,0,0,.5)),url('{{ $articulo->imagen_url }}')">
    <div class="t-art-hero-in">
        <nav class="t-art-hero-bc">
            <a href="{{ route('blog.index') }}">Blog</a>
            @if($articulo->categoria) <span>/</span> <a href="{{ route('blog.categoria', $articulo->categoria->slug) }}">{{ $articulo->categoria->nombre }}</a>@endif
        </nav>
        <h1>{{ $articulo->titulo }}</h1>
        <div class="t-art-hero-meta">
            @if($articulo->publicado_at)<span>{{ $articulo->publicado_at->format('d/m/Y') }}</span>@endif
            <span>{{ $articulo->minutos_lectura }} min de lectura</span>
        </div>
    </div>
</header>
@endif

<div class="t-article-wrap">
    <article class="t-article" style="max-width:none">
        @if(!$cover)
        <nav class="t-bc">
            <a href="{{ route('blog.index') }}">Blog</a>
            @if($articulo->categoria) / <a href="{{ route('blog.categoria', $articulo->categoria->slug) }}">{{ $articulo->categoria->nombre }}</a>@endif
        </nav>
        <h1>{{ $articulo->titulo }}</h1>
        @endif
        @if($articulo->imagen_url && !$cover)<img class="t-article-cover" src="{{ $articulo->imagen_url }}" alt="{{ $articulo->titulo }}">@endif
        <div class="t-article-body" id="t-article-body">
            {!! $articulo->contenido !!}
            @include('tienda.partials.ad-slot', ['h' => 250])
            @if(!empty($articulo->bloques))@include('tienda.partials.bloques', ['bloques' => $articulo->bloques])@endif
        </div>
        @if($articulo->etiquetas->count())
        <div class="t-tags">
            <span class="t-tags-label">Etiquetas:</span>
            @foreach($articulo->etiquetas as $et)
                <a class="t-tag" href="{{ route('blog.etiqueta', $et->slug) }}">{{ $et->nombre }}</a>
            @endforeach
        </div>
        @endif

    </article>

    <aside class="t-aside">
        <div class="t-aside-block">
            @include('tienda.partials.ad-slot', ['h' => 280])
        </div>
        <div class="t-aside-block" style="border-top:0;padding-top:0">
            <div class="t-aside-meta">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 9h18M7 3v3M17 3v3"/><rect x="3" y="5" width="18" height="16" rx="2"/></svg>
                Actualizado {{ $articulo->updated_at->format('d/m/Y') }}
            </div>
            <div class="t-aside-meta">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                {{ $articulo->minutos_lectura }} min de lectura
            </div>
        </div>

        <div class="t-aside-block" id="t-toc">
            <h5>Índice</h5>
            <div id="t-toc-list"></div>
            <span id="t-toc-more">Ver más</span>
        </div>

        @if($articulo->editor)
        <div class="t-aside-block t-author">
            <h5>Autor</h5>
            <a href="{{ $articulo->editor->url }}" class="t-author-row">
                @if($articulo->editor->foto_url)<img src="{{ $articulo->editor->foto_url }}" alt="{{ $articulo->editor->nombre }}" loading="lazy">@endif
                <span><strong>{{ $articulo->editor->nombre }}</strong>@if($articulo->editor->cargo)<br><small>{{ $articulo->editor->cargo }}</small>@endif</span>
            </a>
        </div>
        @elseif($articulo->autor)
        <div class="t-aside-block"><h5>Autor</h5><p>{{ $articulo->autor }}</p></div>
        @endif

        @if($articulo->categoria)
        <div class="t-aside-block">
            <h5>Categoría</h5>
            <div class="t-aside-cats"><a href="{{ route('blog.categoria', $articulo->categoria->slug) }}">{{ $articulo->categoria->nombre }}</a></div>
        </div>
        @endif

        <div class="t-aside-block t-share">
            <h5>Compartir</h5>
            <button class="t-share-btn" id="t-share-btn">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.6 13.5l6.8 4M15.4 6.5l-6.8 4"/></svg>
                Compartir artículo
            </button>
            <div class="t-share-pop" id="t-share-pop">
                <a href="https://wa.me/?text={{ $shareT }}%20{{ $share }}" target="_blank" rel="noopener">WhatsApp</a>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ $share }}" target="_blank" rel="noopener">Facebook</a>
                <a href="https://twitter.com/intent/tweet?url={{ $share }}&text={{ $shareT }}" target="_blank" rel="noopener">X (Twitter)</a>
                <a href="mailto:?subject={{ $shareT }}&body={{ $share }}">Correo</a>
                <button type="button" id="t-copy-link">Copiar enlace</button>
            </div>
        </div>
        <div class="t-aside-block">
            @include('tienda.partials.digest-zona', ['zona' => 'blog_sidebar'])
        </div>
    </aside>
</div>

@if($relacionados->count())
<section class="t-section t-wrap">
    <h2 class="t-h2">Sigue leyendo</h2>
    <div class="t-blog-grid">@foreach($relacionados as $a)@include('blog._card', ['a' => $a])@endforeach</div>
</section>
@endif
@endsection
@if(\App\Models\Ajuste::get('adsense_activo') === '1')
@push('scripts')
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-4712910980244467" crossorigin="anonymous"></script>
@endpush
@endif

@if($cover)
@push('scripts')
<script>
(function(){var hd=document.getElementById('t-hd');if(!hd)return;var f=function(){hd.classList.toggle('is-scrolled',window.scrollY>24);};window.addEventListener('scroll',f,{passive:true});f();})();
</script>
@endpush
@endif
