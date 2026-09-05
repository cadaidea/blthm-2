@php
  $num = $c->estab.'-'.$c->pto_emi.'-'.str_pad($c->secuencial,9,'0',STR_PAD_LEFT);
  $fechaAut = $c->fecha_autorizacion ? \Illuminate\Support\Carbon::parse($c->fecha_autorizacion)->format('d/m/Y H:i:s') : '';
  $extra = is_array($c->extra) ? $c->extra : (json_decode($c->extra ?? '{}', true) ?: []);
  $trans = $extra['transportista'] ?? [];
  $items = is_array($c->detalles) ? $c->detalles : (json_decode($c->detalles ?? '[]', true) ?: []);
@endphp
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8">
<style>
  * { font-family: DejaVu Sans, sans-serif; }
  body { font-size: 10px; color: #1a1a1a; margin: 0; }
  .wrap { padding: 18px 22px; }
  .box { border: 1px solid #d0d4da; border-radius: 6px; padding: 12px 14px; }
  .emisor-name { font-size: 15px; font-weight: bold; color: #161921; }
  .emisor-sub { color: #5b6470; font-size: 9px; line-height: 1.5; margin-top: 4px; }
  .ruc { font-size: 11px; font-weight: bold; }
  .doc-title { font-size: 13px; font-weight: bold; color: #0499FC; letter-spacing: .5px; margin: 6px 0 2px; }
  .doc-num { font-size: 12px; font-weight: bold; }
  .label { color: #8a929c; font-size: 8px; text-transform: uppercase; letter-spacing: .4px; }
  .clave { font-family: DejaVu Sans Mono, monospace; font-size: 8.5px; word-break: break-all; }
  .auth-ok { display: inline-block; background: #e7f7ec; color: #1f8b4c; border: 1px solid #bfe6cd; border-radius: 20px; padding: 2px 10px; font-size: 9px; font-weight: bold; }
  table.data { width: 100%; border-collapse: collapse; margin-top: 4px; }
  table.data th { background: #161921; color: #fff; font-size: 8.5px; text-transform: uppercase; padding: 6px 8px; text-align: left; }
  table.data td { padding: 6px 8px; border-bottom: 1px solid #edeff2; }
  .c { text-align: center; }
  .mt8 { margin-top: 8px; } .mt12 { margin-top: 12px; }
  .info-box { background:#f4f9ff; border:1px solid #d3e6f8; border-radius:6px; padding:10px 14px; margin-top:12px; }
  .grid2 { width:100%; } .grid2 td { vertical-align:top; padding-right:10px; }
</style></head><body>
<div class="wrap">
  <table style="width:100%"><tr>
    <td style="width:56%; vertical-align:top; padding-right:12px;">
      <div class="box" style="min-height:100px;">
        <div class="emisor-name">{{ $emisor['comercial'] ?: $emisor['razon'] }}</div>
        <div class="emisor-sub">
          @if($emisor['comercial'] && $emisor['comercial'] !== $emisor['razon']){{ $emisor['razon'] }}<br>@endif
          <strong>Matriz:</strong> {{ $emisor['dir_matriz'] }}<br>
          <strong>Obligado a llevar contabilidad:</strong> {{ $emisor['obligado'] }}
        </div>
      </div>
    </td>
    <td style="width:44%; vertical-align:top;">
      <div class="box" style="min-height:100px;">
        <div class="ruc">R.U.C.: {{ $emisor['ruc'] }}</div>
        <div class="doc-title">GUÍA DE REMISIÓN</div>
        <div class="doc-num">No. {{ $num }}</div>
        <div class="mt8"><span class="auth-ok">AUTORIZADO</span></div>
      </div>
    </td>
  </tr></table>

  <div class="box mt12">
    <div class="label">Clave de acceso / Autorización</div>
    <div class="clave">{{ $c->numero_autorizacion ?: $c->clave_acceso }}</div>
    <div class="mt8"><span class="label">Fecha autorización:</span> {{ $fechaAut }}</div>
  </div>

  {{-- Transporte --}}
  <div class="info-box">
    <table class="grid2"><tr>
      <td style="width:50%">
        <div class="label">Transportista</div>
        <strong>{{ $trans['razon'] ?? '—' }}</strong><br>
        <span class="label">RUC/CI:</span> {{ $trans['ruc'] ?? '—' }}
        @if(!empty($trans['placa']))<br><span class="label">Placa:</span> {{ $trans['placa'] }}@endif
        @if(!empty($extra['info_adicional']['Chofer']))<br><span class="label">Chofer:</span> {{ $extra['info_adicional']['Chofer'] }}@endif
      </td>
      <td style="width:50%">
        <div class="label">Fechas de traslado</div>
        <span class="label">Inicio:</span> {{ $extra['fecha_ini'] ?? '—' }}<br>
        <span class="label">Fin:</span> {{ $extra['fecha_fin'] ?? '—' }}<br>
        <span class="label">Motivo:</span> {{ $extra['motivo'] ?? 'Entrega de mercadería' }}
      </td>
    </tr></table>
  </div>

  {{-- Destinatario --}}
  <div class="box mt12">
    <div class="label">Destinatario</div>
    <strong>{{ $c->receptor_razon }}</strong> · {{ $c->receptor_identificacion }}<br>
    <span class="label">Dirección:</span> {{ $c->receptor_direccion ?: '—' }}
  </div>

  {{-- Items --}}
  <table class="data mt12">
    <thead><tr><th>Código</th><th>Descripción</th><th class="c">Cantidad</th></tr></thead>
    <tbody>
      @foreach($items as $it)
        <tr>
          <td>{{ $it['codigo'] ?? '' }}</td>
          <td>{{ $it['descripcion'] ?? '' }}</td>
          <td class="c">{{ number_format((float)($it['cantidad'] ?? 0), 2) }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <div class="mt12" style="color:#8a929c; font-size:8.5px;">
    Documento generado electrónicamente · Guía de remisión autorizada por el SRI
  </div>
</div>
</body></html>
