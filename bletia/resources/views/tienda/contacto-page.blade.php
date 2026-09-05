@extends('tienda.layout')
@section('title', ($pagina->meta_title ?: $pagina->titulo) . ' · ' . config('tienda.marca'))
@section('meta_description', $pagina->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($pagina->contenido), 180))
@section('canonical', route('contacto.form'))
@if($pagina->imagen_url)@section('og_image', $pagina->imagen_url)@endif
@section('content')
<div class="t-page">
    <h1 class="t-h1">{{ $pagina->titulo }}</h1>
    @if($pagina->imagen_url)<img class="t-page-cover" src="{{ $pagina->imagen_url }}" alt="{{ $pagina->titulo }}">@endif
    <div class="t-page-body">
        {!! $pagina->contenido !!}
        @if(!empty($pagina->bloques))@include('tienda.partials.bloques', ['bloques' => $pagina->bloques])@endif
    </div>
</div>
@endsection
