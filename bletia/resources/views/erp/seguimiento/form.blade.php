@extends('erp.seguimiento._layout')
@section('title','Seguimiento de pedido')
@section('content')
<h1>Seguimiento de tu pedido</h1>
<p class="muted">Ingresa el número de tu pedido.</p>
@if(isset($error))<div class="err">{{ $error }}</div>@endif
<form method="get" action="{{ url('/seguimiento') }}">
    <input type="text" name="p" placeholder="N° de pedido" required>
    <button type="submit" class="btn">Consultar</button>
</form>
@endsection
