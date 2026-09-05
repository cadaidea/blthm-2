@extends('erp.confirmar._layout')
@section('title', 'Garantía confirmada')
@section('content')
<h1>¡Garantía confirmada!</h1>
<p>Gracias por confirmar. El equipo de Bletia ha sido notificado y coordinará la recolección del producto.</p>
@if($reclamo)
<p><strong>Reclamo:</strong> {{ $reclamo->folio ?: ('#'.$reclamo->id) }}</p>
<p><strong>Bultos a despachar:</strong> {{ $reclamo->bultos ?? 1 }}</p>
<a href="{{ url('/confirmar-garantia/' . $link->token . '/etiquetas') }}" class="btn" style="display:inline-block; margin-top:16px; text-decoration:none;">
    Descargar etiquetas para imprimir
</a>
<p style="color:#5b6470; font-size:13px; margin-top:8px;">Imprime y pega una etiqueta en cada bulto antes de entregar.</p>
@endif
@endsection
