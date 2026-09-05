<!doctype html><html><head><meta charset="utf-8">@include('erp.pdf._base')</head><body><div class="wrap">
<div class="head">
    <table class="row2"><tr>
        <td>@include('erp.pdf._marca')<div class="muted">{{ $empresa['ciudad'] }} @if($empresa['telefono']) · {{ $empresa['telefono'] }}@endif</div></td>
        <td class="right"><div class="doc-title">Resumen de pedido</div><div class="muted">N° {{ $numero }}@if($nro_factura) · Factura {{ $nro_factura }}@endif<br>{{ $fecha }}</div></td>
    </tr></table>
</div>
<div class="box"><h4>Cliente</h4>
    {{ $cliente['nombre'] }}@if($cliente['cedula']) · {{ $cliente['cedula'] }}@endif<br>
    @if($cliente['email']){{ $cliente['email'] }} @endif @if($cliente['telefono'])· {{ $cliente['telefono'] }}@endif
</div>
<table class="tbl"><thead><tr><th>Producto</th><th class="right">Cant.</th><th class="right">Bultos</th><th class="right">P. unit</th><th class="right">Subtotal</th></tr></thead><tbody>
@foreach($items as $it)
    <tr><td>{{ $it['nombre'] }}@if($it['variantes'])<br><span class="muted">{{ $it['variantes'] }}</span>@endif</td>
    <td class="right">{{ rtrim(rtrim(number_format($it['cantidad'],2),'0'),'.') }}</td>
    <td class="right">{{ $it['bultos'] }}</td>
    <td class="right">${{ number_format($it['precio'],2) }}</td>
    <td class="right">${{ number_format($it['subtotal'],2) }}</td></tr>
@endforeach
</tbody></table>
<p class="right total" style="margin-top:12px">Total: ${{ number_format($total,2) }}</p>
@if($vendedor)<p class="muted">Atendido por: {{ $vendedor }}</p>@endif
<div class="foot">Gracias por su compra · {{ $empresa['nombre'] }}</div>
</div>    @include('tienda.partials.cookies')
</body></html>
