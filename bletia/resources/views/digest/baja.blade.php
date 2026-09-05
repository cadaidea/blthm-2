@extends('tienda.layout')
@section('title', 'Baja de suscripción · ' . config('tienda.marca'))
@section('content')
<div class="t-page" style="max-width:560px;text-align:center;padding:40px 0">
@if(!empty($hecho))
    <h1 class="t-h1">Suscripción cancelada</h1>
    <p class="t-lead">Diste de baja {{ $s->email }}. Lamentamos verte ir.</p>
    <p style="margin-top:22px"><a href="{{ route('tienda.home') }}" class="t-btn">Volver</a></p>
@else
    <h1 class="t-h1">¿Cancelar tu suscripción?</h1>
    <p class="t-lead">Dejarás de recibir correos en {{ $s->email }}.</p>
    <form method="post" action="{{ route('digest.unsubscribe') }}" style="margin-top:22px">
        @csrf
        <input type="hidden" name="sid" value="{{ $s->id }}">
        <input type="hidden" name="token" value="{{ $s->token }}">
        <button type="submit" class="t-btn t-btn--cta">Confirmar baja</button>
    </form>
@endif
</div>
@endsection
