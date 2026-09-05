@extends('erp.seguimiento._layout')
@section('title','Pedido ' . $numero)
@section('content')
<h1>Pedido #{{ $numero }}</h1>
<p style="margin:8px 0 0"><span class="badge">{{ $actualLbl }}</span></p>
@if($historial->count())
    <ul class="tl">
    @foreach($historial as $h)
        <li>
            <span class="dot"></span>
            <span class="st">{{ $labels[$h->estado_nuevo] ?? ucfirst(str_replace('_',' ', (string) $h->estado_nuevo)) }}</span>
            <div class="dt">{{ \Illuminate\Support\Carbon::parse($h->creado_en)->format('d/m/Y H:i') }}</div>
        </li>
    @endforeach
    </ul>
@else
    <p class="muted" style="margin-top:18px">Tu pedido está {{ strtolower($actualLbl) }}. Te avisaremos de cada avance por correo.</p>
@endif
<p style="margin-top:20px"><a href="{{ url('/seguimiento') }}" class="muted">Consultar otro pedido</a></p>
@endsection
