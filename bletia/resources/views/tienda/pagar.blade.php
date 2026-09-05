@extends('tienda.layout')
@section('title', 'Pago ' . $pedido->codigo . ' · ' . config('tienda.marca'))

@push('styles')
<link rel="stylesheet" href="https://cdn.payphonetodoesposible.com/box/v2.0/payphone-payment-box.css">
<script type="module" src="https://cdn.payphonetodoesposible.com/box/v2.0/payphone-payment-box.js"></script>
<style>
.t-pay{max-width:520px;margin:0 auto}
.t-pay .resumen{background:#f7f8fa;border:1px solid var(--t-line);border-radius:12px;padding:16px;margin-bottom:18px}
.t-pay .line{display:flex;justify-content:space-between;padding:3px 0;font-size:14px}
.t-pay .big{font-weight:800;border-top:1px solid var(--t-line);margin-top:8px;padding-top:8px}
</style>
@endpush

@section('content')
<div class="t-pay">
    <h1 class="t-h1">Pago seguro</h1>
    <div class="resumen">
        <div class="line"><span>Pedido</span><span>{{ $pedido->codigo }}</span></div>
        <div class="line"><span>Subtotal</span><span>${{ number_format($pedido->subtotal, 2) }}</span></div>
        <div class="line"><span>IVA</span><span>${{ number_format($pedido->iva, 2) }}</span></div>
        <div class="line big"><span>Total a pagar</span><span>${{ number_format($pedido->total, 2) }}</span></div>
    </div>

    {{-- Contenedor donde Payphone renderiza la cajita --}}
    <div id="pp-button"></div>
    <p class="t-iva" style="text-align:center;margin-top:12px">Tus datos de tarjeta se procesan dentro de PayPhone. No los almacenamos.</p>
</div>

<script>
window.addEventListener('DOMContentLoaded', function () {
    new PPaymentButtonBox({
        token: @json(config('payphone.token')),
        storeId: @json(config('payphone.store_id')),
        clientTransactionId: @json($pedido->pp_client_tx),
        amount: {{ $montos['amount'] }},
        amountWithoutTax: {{ $montos['amountWithoutTax'] }},
        amountWithTax: {{ $montos['amountWithTax'] }},
        tax: {{ $montos['tax'] }},
        service: 0,
        tip: 0,
        currency: @json(config('payphone.currency')),
        reference: @json('Pedido ' . $pedido->codigo),
        email: @json($pedido->email ?: optional($pedido->cliente)->email),
        phoneNumber: @json(preg_replace('/\D/', '', (string) (optional($pedido->cliente)->telefono ?: ''))),
        documentId: @json(optional($pedido->cliente)->identificacion ?: ''),
        lang: @json(config('payphone.lang')),
        defaultMethod: 'card'
    }).render('pp-button');
});
</script>
@endsection
