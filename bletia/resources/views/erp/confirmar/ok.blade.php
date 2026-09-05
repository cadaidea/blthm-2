@extends('erp.confirmar._layout')
@section('title', 'Confirmado')
@section('content')
<div class="ok-ico">✓</div>
<h1 class="center">¡Confirmado!</h1>
<p class="center" style="color:#555">Registramos la confirmación correctamente. Gracias.</p>
@if(!empty($esProveedor))
    <a href="{{ url('/confirmar/' . $link->token . '/etiquetas') }}" class="btn" style="display:inline-block;margin-top:16px;text-decoration:none;">Descargar etiquetas para imprimir</a>
    <p style="color:#5b6470;font-size:13px;margin-top:8px;">Imprime y pega una etiqueta en cada bulto.</p>
@endif
@endsection
