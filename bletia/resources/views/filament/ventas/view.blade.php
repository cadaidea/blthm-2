<x-filament-panels::page>
@php
  $tipo = $v->tipo_comprobante === 'factura' ? 'Factura' : ($v->tipo_comprobante === 'nota_venta' ? 'Nota de venta' : 'Comprobante');
  $esFactura = $v->tipo_comprobante === 'factura';
@endphp

<div style="display:flex; flex-direction:column; gap:16px;">

  <div style="background:#fff; border:1px solid #e7e9ed; border-radius:12px; padding:20px;">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px;">
      <div>
        <div style="font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:#8a929c;">{{ $tipo }}</div>
        <div style="font-size:22px; font-weight:bold; color:#161921;">{{ $v->numero_comprobante ?: $v->nro_factura }}</div>
        <div style="color:#5b6470; font-size:13px; margin-top:4px;">
          {{ \Illuminate\Support\Carbon::parse($v->fecha)->format('d/m/Y') }}
          @if($v->codigo_origen) · Origen: <strong>{{ $v->codigo_origen }}</strong>@endif
          @if($v->pedido) · Pedido: <strong>{{ $v->pedido->folio }}</strong>@endif
        </div>
      </div>
      <div>
        @if($v->estado === 'anulada')
          <span style="background:#fde8e8; color:#c0392b; padding:5px 14px; border-radius:20px; font-size:12px; font-weight:bold;">ANULADA</span>
        @elseif($esFactura)
          <span style="background:#e7f7ec; color:#1f8b4c; padding:5px 14px; border-radius:20px; font-size:12px; font-weight:bold;">FACTURA SRI</span>
        @else
          <span style="background:#eef1f4; color:#5b6470; padding:5px 14px; border-radius:20px; font-size:12px; font-weight:bold;">DOCUMENTO INTERNO</span>
        @endif
      </div>
    </div>
  </div>

  <div style="background:#fff; border:1px solid #e7e9ed; border-radius:12px; padding:20px;">
    <div style="font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:#8a929c; margin-bottom:10px;">Cliente</div>
    <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px;">
      <div><div style="font-size:11px; color:#8a929c;">Nombre</div><strong>{{ $v->cliente->nombre ?? '—' }}</strong></div>
      <div><div style="font-size:11px; color:#8a929c;">Identificación</div>{{ $v->cliente->cedula_ruc ?? $v->cliente->identificacion ?? '—' }}</div>
      <div><div style="font-size:11px; color:#8a929c;">Email</div>{{ $v->cliente->email ?? '—' }}</div>
    </div>
  </div>

  <div style="background:#fff; border:1px solid #e7e9ed; border-radius:12px; padding:20px;">
    <div style="font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:#8a929c; margin-bottom:10px;">Ítems</div>
    <table style="width:100%; border-collapse:collapse; font-size:13px;">
      <thead><tr style="text-align:left; color:#8a929c; font-size:11px; text-transform:uppercase;">
        <th style="padding:6px 0;">Descripción</th><th style="text-align:center;">Cant.</th><th style="text-align:right;">Total</th>
      </tr></thead>
      <tbody>
        @foreach($items as $it)
        <tr style="border-top:1px solid #edeff2;">
          <td style="padding:8px 0;"><strong>{{ $it->nombre }}</strong>
            @php $det = \App\Services\Sri\DetalleItem::detalles($it); @endphp
            @if($det)<div style="color:#5b6470; font-size:11px;">{{ $det }}</div>@endif
          </td>
          <td style="text-align:center;">{{ rtrim(rtrim(number_format($it->cantidad,2,'.',''),'0'),'.') }}</td>
          <td style="text-align:right;">$ {{ number_format($it->subtotal,2) }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
    <div style="display:flex; justify-content:flex-end; gap:24px; margin-top:14px; padding-top:14px; border-top:2px solid #161921;">
      <div style="text-align:right;">
        <div style="color:#8a929c; font-size:12px;">Base $ {{ number_format($subtotalCalc,2) }} · IVA $ {{ number_format($ivaCalc,2) }}</div>
        <div style="font-size:20px; font-weight:bold; color:#161921;">$ {{ number_format($totalCalc,2) }}</div>
        @if(isset($saldoCalc))
        <div style="margin-top:10px; padding-top:10px; border-top:1px solid #edeff2;">
          <div style="display:flex; justify-content:space-between; font-size:13px; color:#5b6470;"><span>Pagado</span><strong style="color:#1f8b4c;">$ {{ number_format($pagadoCalc,2) }}</strong></div>
          <div style="display:flex; justify-content:space-between; font-size:14px; margin-top:4px;">
            <span style="color:#5b6470;">Saldo pendiente</span>
            <strong style="color:{{ $saldoCalc > 0 ? '#c0392b' : '#1f8b4c' }};">$ {{ number_format($saldoCalc,2) }}</strong>
          </div>
          @if($saldoCalc > 0)
            <div style="margin-top:6px; background:#fde8e8; color:#c0392b; padding:6px 10px; border-radius:8px; font-size:12px; text-align:center;">Falta cancelar $ {{ number_format($saldoCalc,2) }}</div>
          @else
            <div style="margin-top:6px; background:#e7f7ec; color:#1f8b4c; padding:6px 10px; border-radius:8px; font-size:12px; text-align:center; font-weight:bold;">PAGADO EN SU TOTALIDAD</div>
          @endif
        </div>
        @endif
      </div>
    </div>
  </div>

  @if(!empty($pagos))
  <div style="background:#fff; border:1px solid #e7e9ed; border-radius:12px; padding:20px;">
    <div style="font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:#8a929c; margin-bottom:10px;">Formas de pago</div>
    <div style="display:flex; flex-wrap:wrap; gap:8px;">
      @foreach($pagos as $g)
        <div style="margin-bottom:10px; width:100%;">
          <div style="font-weight:bold; font-size:13px; color:#161921;">{{ $g['etiqueta'] }} — $ {{ number_format($g['total'],2) }}</div>
          @foreach($g['pagos'] as $pago)
            <div style="padding-left:14px; color:#5b6470; font-size:12px;">· {{ $pago['label'] }} $ {{ number_format($pago['monto'],2) }}</div>
          @endforeach
        </div>
      @endforeach
    </div>
  </div>
  @endif

  @if($v->es_credito && (float)$v->saldo_credito > 0)
  @php
    $estado = $v->estadoCredito();
    $colores = ['vencido' => ['#fde8e8','#c0392b','Vencido'], 'por_vencer' => ['#fff5e6','#b9770e','Por vencer'], 'al_dia' => ['#e7f7ec','#1f8b4c','Al día']];
    [$bg,$fg,$txt] = $colores[$estado] ?? $colores['al_dia'];
  @endphp
  <div style="background:#fff; border:1px solid #e7e9ed; border-radius:12px; padding:20px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
      <div style="font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:#8a929c;">Crédito</div>
      <span style="background:{{ $bg }}; color:{{ $fg }}; padding:4px 12px; border-radius:20px; font-size:11px; font-weight:bold;">{{ $txt }}</span>
    </div>
    <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px;">
      <div>
        <div style="font-size:11px; color:#8a929c;">Saldo pendiente</div>
        <strong style="font-size:18px; color:{{ $fg }};">$ {{ number_format((float)$v->saldo_credito,2) }}</strong>
      </div>
      <div>
        <div style="font-size:11px; color:#8a929c;">Plazo</div>
        <strong>{{ $v->credito_plazo_dias ?? '—' }} días</strong>
      </div>
      <div>
        <div style="font-size:11px; color:#8a929c;">Vence</div>
        <strong>{{ $v->credito_vence_at ? $v->credito_vence_at->format('d/m/Y') : '—' }}</strong>
      </div>
    </div>
  </div>
  @endif
  @if($v->info_adicional)
  <div style="background:#fff; border:1px solid #e7e9ed; border-radius:12px; padding:20px;">
    <div style="font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:#8a929c; margin-bottom:6px;">Información adicional</div>
    <div style="color:#3a4250;">{!! nl2br(e($v->info_adicional)) !!}</div>
  </div>
  @endif

</div>
</x-filament-panels::page>
