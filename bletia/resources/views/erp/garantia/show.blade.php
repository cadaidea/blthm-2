@extends('erp.confirmar._layout')
@section('title', 'Confirmar garantía lista')
@section('content')
<h1>Confirmar garantía terminada</h1>
<p>Reclamo: <strong>{{ $reclamo->folio ?: ('#'.$reclamo->id) }}</strong></p>
<p>Producto: <strong>{{ $reclamo->producto ?: '—' }}</strong></p>
@if($reclamo->descripcion)
<p style="color:#5b6470; font-size:14px;">Problema original: {{ $reclamo->descripcion }}</p>
@endif
@if(isset($errors) && $errors->any())
    <div class="err">@foreach($errors->all() as $e){{ $e }}<br>@endforeach</div>
@endif
<form method="post" action="{{ url('/confirmar-garantia/' . $link->token) }}" enctype="multipart/form-data">
    @csrf
    <label>Foto 1 del producto reparado *</label>
    <input type="file" name="foto_1" accept="image/*" capture="environment" required>
    <label>Foto 2 del producto reparado *</label>
    <input type="file" name="foto_2" accept="image/*" capture="environment" required>
    <div class="hint">Sube 2 fotos del producto reparado y listo para despachar.</div>
    <button type="submit" class="btn">Confirmar garantía lista</button>
</form>
@endsection
