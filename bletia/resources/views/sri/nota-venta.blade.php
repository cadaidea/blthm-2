@php
  function nfv($n){ return number_format((float)$n, 2, '.', ','); }
  $num = $venta->numero_comprobante;
  $fecha = \Illuminate\Support\Carbon::parse($venta->fecha)->format('d/m/Y');
  $origen = $venta->codigo_origen ?? null;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
  * { font-family: DejaVu Sans, sans-serif; }
  body { font-size: 10px; color: #1a1a1a; margin: 0; }
  .wrap { padding: 18px 22px; }
  .box { border: 1px solid #d0d4da; border-radius: 6px; padding: 12px 14px; }
  .emisor-name { font-size: 15px; font-weight: bold; color: #161921; }
  .emisor-sub { color: #5b6470; font-size: 9px; line-height: 1.5; margin-top: 4px; }
  .ruc { font-size: 11px; font-weight: bold; }
  .doc-title { font-size: 13px; font-weight: bold; color: #161921; letter-spacing: .5px; margin: 6px 0 2px; }
  .doc-num { font-size: 12px; font-weight: bold; }
  .label { color: #8a929c; font-size: 8px; text-transform: uppercase; letter-spacing: .4px; }
  .pill { display:inline-block; background:#eef1f4; color:#5b6470; border-radius:20px; padding:2px 10px; font-size:9px; font-weight:bold; }
  table.data { width: 100%; border-collapse: collapse; margin-top: 4px; }
  table.data th { background: #161921; color: #fff; font-size: 8.5px; text-transform: uppercase; padding: 6px 8px; text-align: left; }
  table.data td { padding: 6px 8px; border-bottom: 1px solid #edeff2; vertical-align: top; }
  .det { color:#5b6470; font-size:8.5px; margin-top:2px; }
  .r { text-align: right; } .c { text-align: center; }
  .tots { width: 46%; margin-left: 54%; margin-top: 10px; }
  .tots td { padding: 4px 8px; font-size: 10px; }
  .tots tr.grand td { font-size: 12px; font-weight: bold; border-top: 2px solid #161921; }
  .mt8 { margin-top: 8px; } .mt12 { margin-top: 12px; } .muted { color: #8a929c; }
  .pagos-box { margin-top:16px; }
  .pagos-box .label { display:block; margin-bottom:8px; }
  .pago-row { display:inline-block; background:#f4f6f8; border:1px solid #e4e8ec; border-radius:6px; padding:6px 12px; font-size:9px; margin-right:6px; margin-bottom:6px; line-height:1.4; }
</style>
</head>
<body>
<div class="wrap">
  <table style="width:100%"><tr>
    <td style="width:56%; vertical-align:top; padding-right:12px;">
      <div class="box" style="min-height:90px;">
        <div class="emisor-name">{{ $emisor['comercial'] ?: $emisor['razon'] }}</div>
        <div class="emisor-sub">
          @if($emisor['comercial'] && $emisor['comercial'] !== $emisor['razon']){{ $emisor['razon'] }}<br>@endif
          {{ $emisor['dir_matriz'] }}
        </div>
      </div>
    </td>
    <td style="width:44%; vertical-align:top;">
      <div class="box" style="min-height:90px;">
        <div class="ruc">R.U.C.: {{ $emisor['ruc'] }}</div>
        <div class="doc-title">NOTA DE VENTA</div>
        <div class="doc-num">{{ $num }}</div>
        <div class="mt8"><span class="label">Fecha</span> {{ $fecha }}
          @if($origen) &nbsp;·&nbsp; <span class="label">Origen</span> {{ $origen }}@endif
        </div>
        <div class="mt8"><span class="pill">DOCUMENTO INTERNO</span></div>
      </div>
    </td>
  </tr></table>

  <div class="box mt12">
    <table style="width:100%;"><tr>
      <td style="width:60%"><span class="label">Cliente</span><br><strong>{{ $cliente->nombre ?? 'Consumidor final' }}</strong></td>
      <td style="width:40%"><span class="label">Identificación</span><br>{{ $cliente->cedula_ruc ?? $cliente->identificacion ?? '—' }}</td>
    </tr></table>
  </div>

  <table class="data mt12">
    <thead><tr>
      <th style="width:48%">Descripción</th>
      <th style="width:10%" class="c">Cant.</th><th style="width:14%" class="r">P. Unit.</th>
      <th style="width:12%" class="r">Desc.</th><th style="width:16%" class="r">Total</th>
    </tr></thead>
    <tbody>
      @foreach($items as $it)
      <tr>
        <td>
          <strong>{{ $it['nombre'] ?? $it['descripcion'] }}</strong>
          @if(!empty($it['detalles']))<div class="det">{{ $it['detalles'] }}</div>@endif
        </td>
        <td class="c">{{ rtrim(rtrim(number_format($it['cantidad'],2,'.',''),'0'),'.') }}</td>
        <td class="r">{{ nfv($it['precio_unitario']) }}</td>
        <td class="r">{{ nfv($it['descuento']) }}</td>
        <td class="r">{{ nfv($it['total']) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <table class="tots">
    <tr><td class="muted">Subtotal</td><td class="r">{{ nfv($subtotal) }}</td></tr>
    <tr><td class="muted">IVA 15%</td><td class="r">{{ nfv($totalIva) }}</td></tr>
    <tr class="grand"><td>TOTAL</td><td class="r">$ {{ nfv($total) }}</td></tr>
  </table>

  @if(!empty($pagos))
  <div class="pagos-box">
    <span class="label">Formas de pago</span>
    @foreach($pagos as $g)
      <div style="margin-bottom:8px;">
        <div style="font-weight:bold; font-size:10px;">{{ $g['etiqueta'] }} — $ {{ nfv($g['total']) }}</div>
        @if(count($g['pagos']) > 0)
          <div style="padding-left:14px; color:#5b6470; font-size:9px;">
            @foreach($g['pagos'] as $pago)
              · {{ $pago['label'] }} $ {{ nfv($pago['monto']) }}@if(!$loop->last)<br>@endif
            @endforeach
          </div>
        @endif
      </div>
    @endforeach
  </div>
  @endif

  @if(!empty($venta->info_adicional))
  <div class="box mt12">
    <span class="label">Información adicional</span><br>
    {!! nl2br(e($venta->info_adicional)) !!}
  </div>
  @endif

  <div class="mt12 muted" style="font-size:8px; text-align:center; border-top:1px solid #edeff2; padding-top:8px;">
    Nota de venta · documento interno sin validez tributaria · {{ $emisor['comercial'] ?: $emisor['razon'] }}
  </div>
</div>
</body>
</html>
