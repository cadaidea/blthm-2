@extends('tienda.layout')
@section('title', ($aprobado ? 'Compra exitosa' : 'Pago no completado') . ' · ' . config('tienda.marca'))

@section('content')
<div style="max-width:560px;margin:0 auto;text-align:center;padding:30px 0">
    @if($aprobado)
        <h1 class="t-h1">¡Gracias por tu compra! 🎉</h1>
        <p class="t-lead" style="margin:0 auto">Tu pedido <strong>{{ $pedido->codigo }}</strong> fue pagado correctamente.
        Te enviamos la confirmación a <strong>{{ $pedido->email }}</strong>.</p>
        <p style="margin-top:18px;font-weight:800;font-size:20px">Total: ${{ number_format($pedido->total, 2) }}</p>
    @else
        <h1 class="t-h1">Pago no completado</h1>
        <p class="t-lead" style="margin:0 auto">No se pudo confirmar el pago del pedido <strong>{{ $pedido->codigo }}</strong>.
        Si el cargo no se realizó, puedes intentarlo de nuevo.</p>
        <p style="margin-top:16px"><a class="t-btn" style="text-decoration:none;display:inline-block" href="{{ route('checkout.pagar', $pedido->codigo) }}">Reintentar pago</a></p>
    @endif
    <p style="margin-top:24px"><a href="{{ route('tienda.home') }}">Volver a la tienda</a></p>
</div>
@endsection
