@extends('tienda.layout')
@section('title', 'Blog · ' . config('tienda.marca'))
@section('meta_description', 'Ideas, guías y novedades de ' . config('tienda.marca') . '.')
@section('content')
<div class="t-blog-hero">
    <p class="t-eyebrow">Diario</p>
    <h1 class="t-h1" style="margin-top:4px">Blog</h1>
</div>
<div class="t-blog-cats">
    <a class="t-blog-cat is-active" href="{{ route('blog.index') }}">Todo</a>
    @foreach($categorias as $c)<a class="t-blog-cat" href="{{ route('blog.categoria', $c->slug) }}">{{ $c->nombre }}</a>@endforeach
</div>
@if($articulos->count())
    <div class="t-blog-grid">
        @foreach($articulos as $a)@include('blog._card', ['a' => $a])@endforeach
    </div>
    @include('tienda.partials.ad-slot', ['h' => 250])
    <div class="t-pag">{{ $articulos->links() }}</div>
@else
    <p class="t-empty">Aún no hay artículos publicados.</p>
@endif
@if(\App\Models\Ajuste::get('adsense_activo') === '1')
@push('scripts')
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-4712910980244467" crossorigin="anonymous"></script>
@endpush
@endif
@endsection
