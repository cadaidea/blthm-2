@extends('tienda.layout')

@section('title', ($categoria->meta_title ?: $categoria->nombre) . ' · ' . config('tienda.marca'))
@section('meta_description', $categoria->meta_description ?: ('Productos de ' . $categoria->nombre . ' en ' . config('tienda.marca') . '.'))
@section('canonical', route('tienda.categoria', $categoria->slug))

@push('jsonld')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type'    => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => url('/')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => $categoria->nombre, 'item' => route('tienda.categoria', $categoria->slug)],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

@section('content')
    <nav class="t-bc"><a href="{{ route('tienda.home') }}">Inicio</a> / <span>{{ $categoria->nombre }}</span></nav>
    <h1 class="t-h1">{{ $categoria->nombre }}</h1>
    @if($categoria->descripcion)<p class="t-lead">{{ $categoria->descripcion }}</p>@endif

    <section class="t-grid">
        @forelse($productos as $producto)
            @include('tienda.partials.producto-card', ['producto' => $producto])
        @empty
            <p class="t-empty">No hay productos en esta categoría.</p>
        @endforelse
    </section>

    <div class="t-pag">{{ $productos->links() }}</div>
@endsection
