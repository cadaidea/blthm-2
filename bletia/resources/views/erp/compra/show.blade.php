@extends('erp.confirmar._layout')
@section('title', 'Confirmar compra lista')
@section('content')
<h1>Confirmar pedido listo para enviar</h1>
<p>Orden: <strong>{{ $compra->folio ?: ('#'.$compra->id) }}</strong></p>
@if(isset($errors) && $errors->any())
    <div class="err">@foreach($errors->all() as $e){{ $e }}<br>@endforeach</div>
@endif
<form method="post" action="{{ url('/confirmar-compra/' . $link->token) }}" enctype="multipart/form-data">
    @csrf
    <div style="margin:14px 0;padding:12px;background:#f4f9ff;border:1px solid #d3e6f8;border-radius:10px;">
        <strong>Confirma cuántos bultos (paquetes) ocupa cada producto:</strong>
        @foreach($compra->items as $it)
            <div style="margin-top:10px;">
                <label>{{ $it->nombre }} (cant. {{ $it->cantidad }})</label>
                <input type="number" name="bultos[{{ $it->id }}]" value="{{ $it->bultos ?: 1 }}" min="1" required style="width:100px;">
            </div>
        @endforeach
    </div>
    <label>Foto 1 del producto terminado *</label>
    <input type="file" name="foto_1" accept="image/*" capture="environment" required>
    <label>Foto 2 del producto terminado *</label>
    <input type="file" name="foto_2" accept="image/*" capture="environment" required>
    <div class="hint">Sube 2 fotos del pedido listo para despachar.</div>
    <button type="submit" class="btn">Confirmar listo para enviar</button>
</form>
@endsection
