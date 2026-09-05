@extends('erp.confirmar._layout')
@section('title', $cfg['titulo'])
@section('content')
<h1>{{ $cfg['titulo'] }}</h1>
@if(isset($errors) && $errors->any())
    <div class="err">@foreach($errors->all() as $e){{ $e }}<br>@endforeach</div>
@endif
<form method="post" action="{{ url('/confirmar/' . $link->token) }}" enctype="multipart/form-data">
    @csrf
    @if(!empty($cfg['receptor']))
        <label>Nombre de quien {{ $link->tipo === 'transportista' ? 'recibe' : 'retira' }} *</label>
        <input type="text" name="receptor_nombre" value="{{ old('receptor_nombre') }}" required>
        @if($link->tipo === 'cliente_retiro')
            <label>Cédula</label>
            <input type="text" name="receptor_cedula" value="{{ old('receptor_cedula') }}">
        @endif
        <label>Celular</label>
        <input type="tel" name="receptor_celular" value="{{ old('receptor_celular') }}">
    @endif
    @if($link->tipo === 'proveedor' && !empty($itemsBultos))
        <div style="margin:14px 0;padding:12px;background:#f4f9ff;border:1px solid #d3e6f8;border-radius:10px;">
            <strong>Confirma cuántos bultos (paquetes) ocupa cada producto:</strong>
            @foreach($itemsBultos as $it)
                <div style="margin-top:10px;">
                    <label>{{ $it['nombre'] }}</label>
                    <input type="hidden" name="bultos[{{ $it['id'] }}][item_id]" value="{{ $it['id'] }}">
                    <input type="number" name="bultos[{{ $it['id'] }}][cantidad]" value="{{ $it['bultos'] }}" min="1" required style="width:100px;">
                </div>
            @endforeach
        </div>
    @endif
    <label>Foto 1 *</label>
    <input type="file" name="foto_1" accept="image/*" capture="environment" required>
    <label>Foto 2 *</label>
    <input type="file" name="foto_2" accept="image/*" capture="environment" required>
    <div class="hint">Sube 2 fotos del producto {{ $link->tipo === 'proveedor' ? 'terminado' : 'entregado/retirado' }}.</div>
    <button type="submit" class="btn">{{ $cfg['boton'] }}</button>
</form>
@endsection
