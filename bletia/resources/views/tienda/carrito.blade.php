@extends('tienda.layout')
@section('title', 'Carrito · ' . config('tienda.marca'))
@push('styles')
<style>
.t-cart-tbl{width:100%;border-collapse:collapse}
.t-cart-tbl th,.t-cart-tbl td{padding:14px 8px;border-bottom:1px solid var(--line);text-align:left;vertical-align:middle}
.t-cart-tbl img{width:64px;height:64px;object-fit:cover;border-radius:10px}
.t-cart-var{display:block;color:var(--muted);font-size:12px;margin-top:2px}
.t-cart-qty{width:66px;padding:8px;border:1px solid var(--line);border-radius:8px}
.t-cart-tot{max-width:340px;margin-left:auto;margin-top:18px}
.t-cart-tot div{display:flex;justify-content:space-between;padding:4px 0}
.t-cart-tot .big{font-weight:800;font-size:18px;border-top:1px solid var(--line);padding-top:8px;margin-top:6px}
.t-actions{display:flex;justify-content:space-between;align-items:center;margin-top:22px;gap:12px;flex-wrap:wrap}
</style>
@endpush
@section('content')
<h1 class="t-h1">Tu carrito</h1>
@if(! $lineas)
    <div class="t-empty">
        <p>Tu carrito está vacío.</p>
        <a href="{{ \App\Models\Ajuste::get('url_tienda', '/') }}" class="t-btn t-btn--cta" style="margin-top:16px;display:inline-flex">Ver productos</a>
    </div>
@else
<form method="post" action="{{ route('carrito.actualizar') }}">
    @csrf
    <table class="t-cart-tbl">
        <thead><tr><th colspan="2">Producto</th><th>Precio</th><th>Cant.</th><th>Subtotal</th><th></th></tr></thead>
        <tbody>
        @foreach($lineas as $l)
            @php($p = $l['producto'])
            <tr>
                <td>@if($l['img'])<img src="{{ $l['img'] }}" alt="{{ $p->nombre }}" loading="lazy">@endif</td>
                <td>
                    <a href="{{ route('tienda.producto', $p->slug) }}">{{ $p->nombre }}</a>
                    @if($l['label'])<span class="t-cart-var">{{ $l['label'] }}</span>@endif
                    @if($l['mto'])<span class="t-cart-mto">Made to Order</span>@endif
                </td>
                <td>${{ number_format($l['pvp'], 2) }}</td>
                <td><input type="number" name="cantidad[{{ $l['key'] }}]" value="{{ $l['cantidad'] }}" min="0" class="t-cart-qty"></td>
                <td>${{ number_format($l['pvp'] * $l['cantidad'], 2) }}</td>
                <td><button type="submit" form="rm-{{ $loop->index }}" class="t-ic" title="Quitar">&times;</button></td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <div class="t-cart-tot">
        <div><span>Subtotal</span><span>${{ number_format($totales['subtotal'], 2) }}</span></div>
        <div><span>IVA</span><span>${{ number_format($totales['iva'], 2) }}</span></div>
        <div class="big"><span>Total</span><span>${{ number_format($totales['total'], 2) }}</span></div>
    </div>
    <div class="t-actions">
        <button type="submit" class="t-btn">Actualizar carrito</button>
        <a href="{{ route('checkout.form') }}" class="t-btn t-btn--accent">Proceder al pago</a>
    </div>
</form>
@foreach($lineas as $l)
    <form id="rm-{{ $loop->index }}" method="post" action="{{ route('carrito.eliminar') }}">@csrf<input type="hidden" name="key" value="{{ $l['key'] }}"></form>
@endforeach
@endif
@endsection
