<!doctype html><html><head><meta charset="utf-8">@include('erp.pdf._base')</head><body><div class="wrap">
<div class="head"><table class="row2"><tr>
    <td>@include('erp.pdf._marca')</td>
    <td class="right"><div class="doc-title">Documento de entrega</div><div class="muted">N° {{ $numero }}<br>{{ $fecha }}</div></td>
</tr></table></div>
<div class="box"><h4>Cliente</h4>{{ $cliente['nombre'] }}@if($cliente['cedula']) · {{ $cliente['cedula'] }}@endif</div>
<table class="tbl"><thead><tr><th>Producto</th><th class="right">Cantidad</th><th class="right">Bultos</th></tr></thead><tbody>
@foreach($items as $it)
    <tr><td>{{ $it['nombre'] }}</td><td class="right">{{ rtrim(rtrim(number_format($it['cantidad'],2),'0'),'.') }}</td><td class="right">{{ $it['bultos'] }}</td></tr>
@endforeach
</tbody></table>
<table class="row2" style="margin-top:40px"><tr>
    <td><div class="firma">Cliente (nombre + cédula)</div></td>
    <td><div class="firma">Entregó (nombre + cargo)</div></td>
</tr></table>
<div class="foot">{{ $empresa['nombre'] }}</div>
</div>    @include('tienda.partials.cookies')
</body></html>
