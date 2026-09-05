@extends('tienda.layout')
@section('title', 'Datos de compra · ' . config('tienda.marca'))

@push('styles')
<style>
.t-co{display:grid;grid-template-columns:1.3fr 1fr;gap:30px}
.t-co label{display:block;font-size:13px;font-weight:600;margin:10px 0 4px}
.t-co input,.t-co select{width:100%;box-sizing:border-box;padding:10px;border:1px solid var(--t-line);border-radius:8px}
.t-co .row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.t-resumen{background:#f7f8fa;border:1px solid var(--t-line);border-radius:12px;padding:16px;height:max-content}
.t-resumen .line{display:flex;justify-content:space-between;padding:4px 0;font-size:14px}
.t-resumen .big{font-weight:800;border-top:1px solid var(--t-line);margin-top:8px;padding-top:8px}
.t-err{color:#c0392b;font-size:13px;margin-top:6px}
@media(max-width:760px){.t-co{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<h1 class="t-h1">Finalizar compra</h1>

@if($errors->any())
    <div class="t-err">{{ $errors->first() }}</div>
@endif

<form method="post" action="{{ route('checkout.crear') }}" class="t-co">
    @csrf
    <div>
        <h2 class="t-h2" style="margin-top:0">Tus datos</h2>
        <label>Nombre completo *</label>
        <input type="text" name="nombre" value="{{ old('nombre') }}" required>
        <div class="row">
            <div>
                <label>Tipo de identificación *</label>
                <select name="tipo_id" required>
                    <option value="cedula">Cédula</option>
                    <option value="ruc">RUC</option>
                    <option value="pasaporte">Pasaporte</option>
                </select>
            </div>
            <div>
                <label>Identificación *</label>
                <input type="text" name="identificacion" value="{{ old('identificacion') }}" required>
            </div>
        </div>
        <div class="row">
            <div><label>Email *</label><input type="email" name="email" value="{{ old('email') }}" required></div>
            <div><label>Teléfono *</label><input type="text" name="telefono" value="{{ old('telefono') }}" required></div>
        </div>
        <label>Dirección</label>
        <input type="text" name="direccion" value="{{ old('direccion') }}">
        <label>Ciudad</label>
        <input type="text" name="ciudad" value="{{ old('ciudad', config('tienda.ciudad')) }}">
    </div>

    <div class="t-resumen">
        <h2 class="t-h2" style="margin-top:0">Tu pedido</h2>
        @foreach($lineas as $l)
            <div class="line"><span>{{ $l['producto']->nombre }} × {{ $l['cantidad'] }}@if($l['label'])<br><small style="color:var(--muted)">{{ $l['label'] }}</small>@endif @if($l['mto'])<small style="color:var(--mto-text,#263D3A)"> · Made to Order</small>@endif</span>
                <span>${{ number_format($l['pvp'] * $l['cantidad'], 2) }}</span></div>
        @endforeach
        <div class="line"><span>Subtotal</span><span>${{ number_format($totales['subtotal'], 2) }}</span></div>
        <div class="line"><span>IVA</span><span>${{ number_format($totales['iva'], 2) }}</span></div>
        <div class="line big"><span>Total</span><span>${{ number_format($totales['total'], 2) }}</span></div>
        <div style="margin-top:10px"><label>Cupón (opcional)</label><input type="text" name="cupon" value="{{ old('cupon') }}" placeholder="Ingresa tu código"></div>
            <button type="submit" class="t-btn" style="width:100%;margin-top:14px">Continuar al pago</button>
        <p class="t-iva" style="text-align:center;margin-top:8px">Pago seguro con PayPhone</p>
    </div>
</form>
@endsection
