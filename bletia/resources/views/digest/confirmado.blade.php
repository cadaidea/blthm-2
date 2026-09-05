@extends('tienda.layout')
@section('title', 'Suscripción confirmada · ' . config('tienda.marca'))
@section('content')
<div class="t-page" style="max-width:560px;text-align:center;padding:40px 0">
    <h1 class="t-h1">{{ $estado === 'ya' ? 'Ya estabas confirmado' : '¡Suscripción confirmada!' }}</h1>
    <p class="t-lead">{{ $estado === 'ya' ? 'Tu correo ya estaba activo. Gracias por seguirnos.' : 'Gracias por confirmar. Empezarás a recibir nuestras novedades.' }}</p>
    <p style="margin-top:22px"><a href="{{ route('tienda.home') }}" class="t-btn">Ir a la tienda</a></p>
</div>
@endsection
