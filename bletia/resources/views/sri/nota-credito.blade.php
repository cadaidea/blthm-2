@php
  function nfc($n){ return number_format((float)$n, 2, '.', ','); }
  $num = $c->estab.'-'.$c->pto_emi.'-'.str_pad($c->secuencial,9,'0',STR_PAD_LEFT);
  $fechaAut = $c->fecha_autorizacion ? \Illuminate\Support\Carbon::parse($c->fecha_autorizacion)->format('d/m/Y H:i:s') : '';
  $extra = is_array($c->extra) ? $c->extra : (json_decode($c->extra ?? '{}', true) ?: []);
  $dm = $extra['doc_modificado'] ?? [];
  $motivo = $extra['motivo'] ?? '';
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
  .doc-title { font-size: 13px; font-weight: bold; color: #c0392b; letter-spacing: .5px; margin: 6px 0 2px; }
  .doc-num { font-size: 12px; font-weight: bold; }
  .label { color: #8a929c; font-size: 8px; text-transform: uppercase; letter-spacing: .4px; }
  .clave { font-family: DejaVu Sans Mono, monospace; font-size: 8.5px; word-break: break-all; }
  .auth-ok { display: inline-block; background: #e7f7ec; color: #1f8b4c; border: 1px solid #bfe6cd; border-radius: 20px; padding: 2px 10px; font-size: 9px; font-weight: bold; }
  table.data { width: 100%; border-collapse: collapse; margin-top: 4px; }
  table.data th { background: #161921; color: #fff; font-size: 8.5px; text-transform: uppercase; padding: 6px 8px; text-align: left; }
  table.data td { padding: 6px 8px; border-bottom: 1px solid #edeff2; }
  .r { text-align: right; } .c { text-align: center; }
  .tots { width: 46%; margin-left: 54%; margin-top: 10px; }
  .tots td { padding: 4px 8px; font-size: 10px; }
  .tots tr.grand td { font-size: 12px; font-weight: bold; border-top: 2px solid #161921; }
  .mt8 { margin-top: 8px; } .mt12 { margin-top: 12px; } .muted { color: #8a929c; }
  .ref-box { background:#fdf3f2; border:1px solid #f3d6d3; border-radius:6px; padding:10px 14px; margin-top:12px; }
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
        <div class="doc-title">NOTA DE CRÉDITO</div>
        <div class="doc-num">No. {{ $num }}</div>
        <div class="mt8"><span class="label">Número de Autorización</span><br><span class="clave">{{ $c->numero_autorizacion ?: $c->clave_acceso }}</span></div>
        <div class="mt8"><span class="label">Fecha Autorización</span><br>{{ $fechaAut }}</div>
        <div class="mt8">
          @if($c->estado === 'AUTORIZADO')<span class="auth-ok">● AUTORIZADO</span>@else<span class="auth-ok" style="background:#fff5e6;color:#b9770e;border-color:#f0d9ad;">● {{ $c->estado }}</span>@endif
        </div>
      </div>
    </td>
  </tr></table>

  <div class="box mt12"><span class="label">Clave de Acceso</span><br><span class="clave">{{ $c->clave_acceso }}</span></div>

  <div class="ref-box">
    <span class="label">Comprobante que se modifica</span><br>
    <strong>Factura {{ $dm['num'] ?? '—' }}</strong> · emitida {{ $dm['fecha_emision'] ?? '—' }}<br>
    <span class="label">Motivo:</span> {{ $motivo }}
  </div>

  <div class="box mt12">
    <table style="width:100%;"><tr>
      <td style="width:60%"><span class="label">Razón Social / Cliente</span><br><strong>{{ $c->receptor_razon }}</strong></td>
      <td style="width:40%"><span class="label">Identificación</span><br>{{ $c->receptor_identificacion }}</td>
    </tr></table>
  </div>

  <table class="data mt12">
    <thead><tr>
      <th style="width:10%">Cód.</th><th style="width:46%">Descripción</th>
      <th style="width:9%" class="c">Cant.</th><th style="width:13%" class="r">P. Unit.</th>
      <th style="width:10%" class="r">Desc.</th><th style="width:12%" class="r">Total</th>
    </tr></thead>
    <tbody>
      @foreach($items as $it)
      <tr>
        <td>{{ $it['codigo'] ?? '' }}</td><td>{{ $it['descripcion'] ?? '' }}</td>
        <td class="c">{{ rtrim(rtrim(number_format($it['cantidad'] ?? 1,2,'.',''),'0'),'.') }}</td>
        <td class="r">{{ nfc($it['precio_unitario'] ?? 0) }}</td>
        <td class="r">{{ nfc($it['descuento'] ?? 0) }}</td>
        <td class="r">{{ nfc($it['total'] ?? 0) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <table class="tots">
    <tr><td class="muted">Subtotal</td><td class="r">{{ nfc($subtotal) }}</td></tr>
    <tr><td class="muted">IVA 15%</td><td class="r">{{ nfc($totalIva) }}</td></tr>
    <tr class="grand"><td>VALOR MODIFICACIÓN</td><td class="r">$ {{ nfc($total) }}</td></tr>
  </table>

  <div class="mt12 muted" style="font-size:8px; text-align:center; border-top:1px solid #edeff2; padding-top:8px;">
    Representación Impresa del Documento Electrónico (RIDE) · Nota de Crédito · {{ $emisor['comercial'] ?: $emisor['razon'] }}
  </div>
</div></body></html>
