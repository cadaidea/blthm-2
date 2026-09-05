@extends('tienda.layout')
@section('title', 'Guardados · ' . config('tienda.marca'))
@section('content')
<div style="max-width:1100px;margin:0 auto">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:8px">
        <h1 class="t-h1" style="margin:0">Mis guardados</h1>
        <a href="{{ route('cuenta.panel') }}" class="t-btn">Volver a mi cuenta</a>
    </div>

    @if($productos->isEmpty())
        <p class="t-empty">Todavía no has guardado ningún producto. Toca el corazón en cualquier mueble que te guste para tenerlo aquí.
            <a href="{{ route('tienda.shop') }}" style="color:var(--brand)">Ver la tienda</a>
        </p>
    @else
        <div class="t-grid">
            @foreach($productos as $producto)
                @include('tienda.partials.producto-card', ['producto' => $producto])
            @endforeach
        </div>
    @endif
</div>
@endsection
