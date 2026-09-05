<!doctype html><html><head><meta charset="utf-8">@include('erp.pdf._base')</head><body><div class="wrap">
<div class="head">
    <table class="row2"><tr>
        <td>@include('erp.pdf._marca')</td>
        <td class="right"><div class="doc-title">Orden de producción interna</div>
        <div class="muted">N° {{ $numero }}<br>{{ $fecha }}</div></td>
    </tr></table>
</div>
<div class="box"><h4>Destino</h4>
    Stock propio · {{ $destino }}
</div>
@foreach($items as $it)
<div class="box">
    <table class="row2"><tr>
        <td style="width:30%">@if($it['foto'])<img src="{{ $it['foto'] }}" style="max-width:130px;max-height:130px">@else<span class="muted">[sin foto]</span>@endif</td>
        <td>
            <h4>{{ $it['nombre'] }}</h4>
            <span class="muted">Cantidad:</span> {{ rtrim(rtrim(number_format($it['cantidad'],2),'0'),'.') }} ·
            <span class="muted">Bultos:</span> {{ $it['bultos'] }}
        </td>
    </tr></table>
</div>
@endforeach
@if(count($materiales))
<div class="box"><h4>Materiales necesarios (según ficha del producto)</h4>
    <table class="row2" style="width:100%">
        @foreach($materiales as $m)
        <tr><td>{{ $m['nombre'] }}</td><td class="right">{{ number_format($m['cantidad'], 2) }} {{ $m['unidad'] }}</td></tr>
        @endforeach
    </table>
</div>
@endif
<div class="foot">{{ $empresa['nombre'] }} · {{ $empresa['ciudad'] }}</div>
</div>
</body></html>
