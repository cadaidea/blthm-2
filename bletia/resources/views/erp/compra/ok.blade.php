@extends('erp.confirmar._layout')
@section('title', 'Confirmado')
@section('content')
<h1>¡Confirmado!</h1>
<p>Gracias. El equipo de Bletia ha sido notificado y coordinará el traslado.</p>
@if($compra)
<p><strong>Orden:</strong> {{ $compra->folio ?: ('#'.$compra->id) }}</p>
<a href="{{ url('/confirmar-compra/' . $link->token . '/etiquetas') }}" class="btn" style="display:inline-block; margin-top:16px; text-decoration:none;">
    Descargar etiquetas para imprimir
</a>
<p style="color:#5b6470; font-size:13px; margin-top:8px;">Imprime y pega una etiqueta en cada bulto antes de despachar.</p>
@endif
@endsection
