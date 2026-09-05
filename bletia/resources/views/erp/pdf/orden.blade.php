<!doctype html><html><head><meta charset="utf-8">@include('erp.pdf._base')</head><body><div class="wrap">
<div class="head">
    <table class="row2"><tr>
        <td>@include('erp.pdf._marca')</td>
        <td class="right"><div class="doc-title">{{ $conCliente ? 'Orden completa' : 'Orden de fabricación' }}</div>
        <div class="muted">N° {{ $numero }}@if($nro_contable) · Contable {{ $nro_contable }}@endif<br>{{ $fecha }}</div></td>
    </tr></table>
</div>
@if($conCliente)
<div class="box"><h4>Datos del cliente</h4>
    {{ $cliente['nombre'] }}@if($cliente['cedula']) · {{ $cliente['cedula'] }}@endif<br>
    @if($cliente['email']){{ $cliente['email'] }}<br>@endif
    @if($cliente['telefono'])Tel: {{ $cliente['telefono'] }} @endif @if($cliente['celular'])· Cel: {{ $cliente['celular'] }}@endif<br>
    @if($cliente['direccion']){{ $cliente['direccion'] }}, @endif{{ $cliente['ciudad'] }} {{ $cliente['provincia'] }}
</div>
@endif
@foreach($items as $it)
<div class="box">
    <table class="row2"><tr>
        <td style="width:30%">@if($it['foto'])<img src="{{ $it['foto'] }}" style="max-width:130px;max-height:130px">@else<span class="muted">[sin foto]</span>@endif</td>
        <td>
            <h4>{{ $it['nombre'] }}</h4>
            <span class="muted">Cantidad:</span> {{ rtrim(rtrim(number_format($it['cantidad'],2),'0'),'.') }} ·
            <span class="muted">Bultos:</span> {{ $it['bultos'] }}<br>
            @if($it['tapiz_principal'])<span class="muted">Tapiz principal:</span> {{ $it['tapiz_principal'] }}<br>@endif
            @if($it['tapiz_secundario'])<span class="muted">Tapiz secundario:</span> {{ $it['tapiz_secundario'] }}<br>@endif
            @if($it['cojines'])<span class="muted">Cojines:</span> {{ $it['cojines'] }}<br>@endif
            @if($it['lacado'])<span class="muted">Lacado:</span> {{ $it['lacado'] }}<br>@endif
            @if($it['notas'])<span class="muted">Notas:</span> {{ $it['notas'] }}@endif
        </td>
    </tr></table>
</div>
@endforeach
@if($vendedor)<p class="muted">Vendedor: {{ $vendedor }}</p>@endif
<div class="foot">{{ $empresa['nombre'] }} · {{ $empresa['ciudad'] }}</div>
</div>    @include('tienda.partials.cookies')
</body></html>
