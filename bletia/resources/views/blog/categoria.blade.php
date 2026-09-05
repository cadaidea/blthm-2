@extends('tienda.layout')
@section('title', $blogCategoria->nombre . ' · Blog · ' . config('tienda.marca'))
@section('content')
<div class="t-blog-hero">
    <p class="t-eyebrow">Blog</p>
    <h1 class="t-h1" style="margin-top:4px">{{ $blogCategoria->nombre }}</h1>
</div>
<div class="t-blog-cats">
    <a class="t-blog-cat" href="{{ route('blog.index') }}">Todo</a>
    @foreach($categorias as $c)
        <a class="t-blog-cat {{ $c->id === $blogCategoria->id ? 'is-active' : '' }}" href="{{ route('blog.categoria', $c->slug) }}">{{ $c->nombre }}</a>
    @endforeach
</div>
@if($articulos->count())
    <div class="t-blog-grid">@foreach($articulos as $a)@include('blog._card', ['a' => $a])@endforeach</div>
    <div class="t-pag">{{ $articulos->links() }}</div>
@else
    <p class="t-empty">Sin artículos en esta categoría.</p>
@endif
@endsection
