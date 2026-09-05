<!doctype html><html><head><meta charset="utf-8">@include('erp.pdf._base')</head><body><div class="wrap">
<div class="head"><table class="row2"><tr>
    <td>@include('erp.pdf._marca')</td>
    <td class="right"><div class="doc-title">Guía de remisión</div><div class="muted">N° {{ $numero }}<br>{{ $fecha }}</div></td>
</tr></table></div>
<table class="row2"><tr>
    <td><div class="box"><h4>Remitente</h4>{{ $empresa['nombre'] }}<br>{{ $empresa['ciudad'] }}@if($empresa['telefono'])<br>{{ $empresa['telefono'] }}@endif</div></td>
    <td><div class="box"><h4>Destinatario</h4>{{ $cliente['nombre'] }}@if($cliente['cedula']) · {{ $cliente['cedula'] }}@endif<br>
        @if($cliente['direccion']){{ $cliente['direccion'] }}<br>@endif{{ $cliente['ciudad'] }} {{ $cliente['provincia'] }}@if($cliente['celular'])<br>Cel: {{ $cliente['celular'] }}@endif</div></td>
</tr></table>
@if($despacho)
<div class="box"><h4>Datos del transporte</h4>
    @if(!empty($despacho->transportista_nombre))Empresa: {{ $despacho->transportista_nombre }}<br>@endif
    @if(!empty($despacho->conductor_nombre))Conductor: {{ $despacho->conductor_nombre }}@endif
    @if(!empty($despacho->conductor_nui)) · NUI: {{ $despacho->conductor_nui }}@endif<br>
    @if(!empty($despacho->conductor_celular))Cel: {{ $despacho->conductor_celular }} @endif
    @if(!empty($despacho->conductor_correo))· {{ $despacho->conductor_correo }} @endif
    @if(!empty($despacho->placa))· Placa: {{ $despacho->placa }}@endif
</div>
@endif
<table class="tbl"><thead><tr><th>Descripción</th><th class="right">Cantidad</th><th class="right">Bultos</th></tr></thead><tbody>
@foreach($items as $it)
    <tr><td>{{ $it['nombre'] }}</td><td class="right">{{ rtrim(rtrim(number_format($it['cantidad'],2),'0'),'.') }}</td><td class="right">{{ $it['bultos'] }}</td></tr>
@endforeach
</tbody></table>
<p class="right" style="margin-top:8px"><strong>Total bultos: {{ $total_bultos }}</strong></p>
<table class="row2" style="margin-top:30px"><tr>
    <td><div class="firma">Entregué conforme (remitente)</div></td>
    <td><div class="firma">Recibí conforme (nombre y cédula)</div></td>
</tr></table>
</div>    @include('tienda.partials.cookies')
</body></html>
