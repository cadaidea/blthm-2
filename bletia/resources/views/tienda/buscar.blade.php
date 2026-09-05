@extends('tienda.layout')
@section('title', 'Buscar: ' . $q . ' · ' . config('tienda.marca'))
@section('content')
<div class="t-shop" style="padding-top:24px">
<h1 class="t-h1">Resultados para "{{ $q }}"</h1>
@php($vacio = $productos->isEmpty() && $categorias->isEmpty() && $paginas->isEmpty() && $articulos->isEmpty() && $blog_categorias->isEmpty() && $etiquetas->isEmpty() && $autores->isEmpty())
@if(! $q || $vacio)
    <p class="t-empty">No se encontraron resultados.</p>
@else
    @if($productos->isNotEmpty())
        <h2 class="t-sr-h">Productos</h2>
        <section class="t-grid">
            @foreach($productos as $producto)
                @include('tienda.partials.producto-card', ['producto' => $producto])
            @endforeach
        </section>
    @endif
    @if($categorias->isNotEmpty())
        <h2 class="t-sr-h">Categorías</h2>
        <ul class="t-sr-list">@foreach($categorias as $c)<li><a href="{{ route('tienda.categoria', $c->slug) }}">{{ $c->nombre }}</a></li>@endforeach</ul>
    @endif
    @if($articulos->isNotEmpty())
        <h2 class="t-sr-h">Artículos</h2>
        <ul class="t-sr-list">@foreach($articulos as $a)<li><a href="{{ $a->categoria ? route('blog.articulo', [$a->categoria->slug, $a->slug]) : route('blog.index') }}">{{ $a->titulo }}</a></li>@endforeach</ul>
    @endif
    @if($blog_categorias->isNotEmpty())
        <h2 class="t-sr-h">Blog · Categorías</h2>
        <ul class="t-sr-list">@foreach($blog_categorias as $c)<li><a href="{{ route('blog.categoria', $c->slug) }}">{{ $c->nombre }}</a></li>@endforeach</ul>
    @endif
    @if($etiquetas->isNotEmpty())
        <h2 class="t-sr-h">Etiquetas</h2>
        <ul class="t-sr-list">@foreach($etiquetas as $e)<li><a href="{{ route('blog.etiqueta', $e->slug) }}">{{ $e->nombre }}</a></li>@endforeach</ul>
    @endif
    @if($autores->isNotEmpty())
        <h2 class="t-sr-h">Autores</h2>
        <ul class="t-sr-list">@foreach($autores as $e)<li><a href="{{ route('blog.autor', $e->slug) }}">{{ $e->nombre }}</a></li>@endforeach</ul>
    @endif
    @if($paginas->isNotEmpty())
        <h2 class="t-sr-h">Páginas</h2>
        <ul class="t-sr-list">@foreach($paginas as $p)<li><a href="{{ url('/' . $p->slug) }}">{{ $p->titulo }}</a></li>@endforeach</ul>
    @endif
@endif
</div>
@endsection
