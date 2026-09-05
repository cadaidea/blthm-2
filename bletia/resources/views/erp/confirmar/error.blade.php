@extends('erp.confirmar._layout')
@section('title', 'Enlace no válido')
@section('content')
<h1 class="center">Enlace no disponible</h1>
<p class="center" style="color:#555">{{ $msg ?? 'Este enlace no es válido.' }}</p>
@endsection
