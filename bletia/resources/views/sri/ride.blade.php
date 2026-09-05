@php
  function nf($n){ return number_format((float)$n, 2, '.', ','); }
  $tipoNombre = ['01'=>'FACTURA','04'=>'NOTA DE CRÉDITO','05'=>'NOTA DE DÉBITO','06'=>'GUÍA DE REMISIÓN','07'=>'COMPROBANTE DE RETENCIÓN'][$c->cod_doc] ?? 'COMPROBANTE';
  $num = $c->estab.'-'.$c->pto_emi.'-'.$c->secuencial;
  $fechaAut = $c->fecha_autorizacion ? \Illuminate\Support\Carbon::parse($c->fecha_autorizacion)->format('d/m/Y H:i:s') : '';
  $pagos = $pagos ?? [];
  $origen = $origen ?? null;
  $infoAdicional = $infoAdicional ?? null;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
  * { font-family: DejaVu Sans, sans-serif; }
  body { font-size: 10px; color: #1a1a1a; margin: 0; }
  .wrap { padding: 18px 22px; }
  .top { width: 100%; }
  .top td { vertical-align: top; }
  .box { border: 1px solid #d0d4da; border-radius: 6px; padding: 12px 14px; }
  .emisor-name { font-size: 15px; font-weight: bold; color: #161921; }
  .emisor-sub { color: #5b6470; font-size: 9px; line-height: 1.5; margin-top: 4px; }
  .doc-box { text-align: left; }
  .ruc { font-size: 11px; font-weight: bold; }
  .doc-title { font-size: 13px; font-weight: bold; color: #0499FC; letter-spacing: .5px; margin: 6px 0 2px; }
  .doc-num { font-size: 12px; font-weight: bold; }
  .label { color: #8a929c; font-size: 8px; text-transform: uppercase; letter-spacing: .4px; }
  .clave { font-family: DejaVu Sans Mono, monospace; font-size: 8.5px; word-break: break-all; letter-spacing: .3px; }
  .auth-ok { display: inline-block; background: #e7f7ec; color: #1f8b4c; border: 1px solid #bfe6cd; border-radius: 20px; padding: 2px 10px; font-size: 9px; font-weight: bold; }
  .auth-pend { background: #fff5e6; color: #b9770e; border-color: #f0d9ad; }
  table.data { width: 100%; border-collapse: collapse; margin-top: 4px; }
  table.data th { background: #161921; color: #fff; font-size: 8.5px; text-transform: uppercase; letter-spacing: .3px; padding: 6px 8px; text-align: left; }
  table.data td { padding: 6px 8px; border-bottom: 1px solid #edeff2; vertical-align: top; }
  .det { color:#5b6470; font-size:8.5px; margin-top:2px; }
  .r { text-align: right; }
  .c { text-align: center; }
  .tots { width: 46%; margin-left: 54%; margin-top: 10px; }
  .tots td { padding: 4px 8px; font-size: 10px; }
  .tots tr.grand td { font-size: 12px; font-weight: bold; border-top: 2px solid #161921; color: #161921; }
  .mt8 { margin-top: 8px; } .mt12 { margin-top: 12px; }
  .muted { color: #8a929c; }
  .pagos-box { margin-top:16px; }
  .pagos-box .label { display:block; margin-bottom:8px; }
  .pago-row { display:inline-block; background:#f4f6f8; border:1px solid #e4e8ec; border-radius:6px; padding:6px 12px; font-size:9px; margin-right:6px; margin-bottom:6px; line-height:1.4; }
</style>
</head>
<body>
<div class="wrap">

  <table class="top">
    <tr>
      <td style="width: 56%; padding-right: 12px;">
        <div class="box" style="min-height: 110px;">
          <div class="emisor-name">{{ $emisor['comercial'] ?: $emisor['razon'] }}</div>
          <div class="emisor-sub">
            @if($emisor['comercial'] && $emisor['comercial'] !== $emisor['razon'])
              {{ $emisor['razon'] }}<br>
            @endif
            <strong>Matriz:</strong> {{ $emisor['dir_matriz'] }}<br>
            @if($emisor['dir_estab'] && $emisor['dir_estab'] !== $emisor['dir_matriz'])
              <strong>Sucursal:</strong> {{ $emisor['dir_estab'] }}<br>
            @endif
            <strong>Obligado a llevar contabilidad:</strong> {{ $emisor['obligado'] }}
            @if($emisor['contribuyente_especial'])<br><strong>Contribuyente Especial Nro:</strong> {{ $emisor['contribuyente_especial'] }}@endif
            @if($emisor['regimen_micro'])<br>{{ $emisor['regimen_micro'] }}@endif
          </div>
        </div>
      </td>
      <td style="width: 44%;">
        <div class="box doc-box" style="min-height: 110px;">
          <div class="ruc">R.U.C.: {{ $emisor['ruc'] }}</div>
          <div class="doc-title">{{ $tipoNombre }}</div>
          <div class="doc-num">No. {{ $num }}</div>
          <div class="mt8"><span class="label">Número de Autorización</span><br>
            <span class="clave">{{ $c->numero_autorizacion ?: $c->clave_acceso }}</span></div>
          <div class="mt8"><span class="label">Fecha y Hora de Autorización</span><br>{{ $fechaAut }}
            @if($origen)<br><span class="label">Origen</span> {{ $origen }}@endif
          </div>
          <div class="mt8">
            <span class="label">Ambiente:</span> <strong>{{ $emisor['ambiente'] }}</strong> &nbsp;
            <span class="label">Emisión:</span> <strong>NORMAL</strong>
          </div>
          <div class="mt8">
            @if($c->estado === 'AUTORIZADO')
              <span class="auth-ok">● AUTORIZADO</span>
            @else
              <span class="auth-ok auth-pend">● {{ $c->estado }}</span>
            @endif
          </div>
        </div>
      </td>
    </tr>
  </table>

  <div class="box mt12">
    <span class="label">Clave de Acceso</span><br>
    <span class="clave">{{ $c->clave_acceso }}</span>
  </div>

  <div class="box mt12">
    <table style="width:100%;">
      <tr>
        <td style="width:50%"><span class="label">Razón Social / Nombres y Apellidos</span><br><strong>{{ $c->receptor_razon }}</strong></td>
        <td style="width:25%"><span class="label">Identificación</span><br>{{ $c->receptor_identificacion }}</td>
        <td style="width:25%"><span class="label">Fecha Emisión</span><br>{{ \Illuminate\Support\Carbon::parse($c->created_at)->format('d/m/Y') }}</td>
      </tr>
      @if($c->receptor_direccion || $c->receptor_telefono || $c->receptor_email)
      <tr>
        <td class="mt8"><span class="label">Dirección</span><br>{{ $c->receptor_direccion ?: '—' }}</td>
        <td class="mt8"><span class="label">Teléfono</span><br>{{ $c->receptor_telefono ?: '—' }}</td>
        <td class="mt8"><span class="label">Email</span><br>{{ $c->receptor_email ?: '—' }}</td>
      </tr>
      @endif
    </table>
  </div>

  <table class="data mt12">
    <thead>
      <tr>
        <th style="width:10%">Cód.</th>
        <th style="width:44%">Descripción</th>
        <th style="width:9%" class="c">Cant.</th>
        <th style="width:13%" class="r">P. Unit.</th>
        <th style="width:11%" class="r">Desc.</th>
        <th style="width:13%" class="r">Total</th>
      </tr>
    </thead>
    <tbody>
      @foreach($items as $it)
      <tr>
        <td>{{ $it['codigo'] }}</td>
        <td>{{ $it['descripcion'] }}</td>
        <td class="c">{{ rtrim(rtrim(number_format($it['cantidad'],2,'.',''),'0'),'.') }}</td>
        <td class="r">{{ nf($it['precio_unitario']) }}</td>
        <td class="r">{{ nf($it['descuento']) }}</td>
        <td class="r">{{ nf($it['total']) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <table class="tots">
    <tr><td class="muted">Subtotal sin impuestos</td><td class="r">{{ nf($subtotal) }}</td></tr>
    @if($totalDesc > 0)<tr><td class="muted">Total descuento</td><td class="r">{{ nf($totalDesc) }}</td></tr>@endif
    <tr><td class="muted">IVA 15%</td><td class="r">{{ nf($totalIva) }}</td></tr>
    <tr class="grand"><td>VALOR TOTAL</td><td class="r">$ {{ nf($total) }}</td></tr>
  </table>

  @if(!empty($pagos))
  <div class="pagos-box">
    <span class="label">Formas de pago</span>
    @foreach($pagos as $g)
      <div style="margin-bottom:8px;">
        <div style="font-weight:bold; font-size:10px;">{{ $g['etiqueta'] }} — $ {{ nf($g['total']) }}</div>
        @if(count($g['pagos']) > 0)
          <div style="padding-left:14px; color:#5b6470; font-size:9px;">
            @foreach($g['pagos'] as $pago)
              · {{ $pago['label'] }} $ {{ nf($pago['monto']) }}@if(!$loop->last)<br>@endif
            @endforeach
          </div>
        @endif
      </div>
    @endforeach
  </div>
  @endif

  @if(!empty($infoAdicional))
  <div class="box mt12">
    <span class="label">Información adicional</span><br>
    {!! nl2br(e($infoAdicional)) !!}
  </div>
  @endif

  <div class="mt12 muted" style="font-size:8px; text-align:center; border-top:1px solid #edeff2; padding-top:8px;">
    Documento generado electrónicamente · Representación Impresa del Documento Electrónico (RIDE) · {{ $emisor['comercial'] ?: $emisor['razon'] }}
  </div>

</div>
</body>
</html>
