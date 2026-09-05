@extends('tienda.layout')
@section('title', $titulo . ' · Blog · ' . config('tienda.marca'))
@section('content')
<div class="t-blog-hero">
    <p class="t-eyebrow">Blog</p>
    <h1 class="t-h1" style="margin-top:4px">{{ $titulo }}</h1>
    @isset($editor)
        @if($editor->cargo)<p class="t-lead">{{ $editor->cargo }}</p>@endif
        @if($editor->bio)<p class="t-lead">{{ $editor->bio }}</p>@endif
    @endisset
</div>
<div class="t-blog-cats">
    <a class="t-blog-cat" href="{{ route('blog.index') }}">Todo</a>
    @foreach($categorias as $c)<a class="t-blog-cat" href="{{ route('blog.categoria', $c->slug) }}">{{ $c->nombre }}</a>@endforeach
</div>
@if($articulos->count())
    <div class="t-blog-grid">@foreach($articulos as $a)@include('blog._card', ['a' => $a])@endforeach</div>
    <div class="t-pag">{{ $articulos->links() }}</div>
@else
    <p class="t-empty">Sin artículos.</p>
@endif
@endsection
