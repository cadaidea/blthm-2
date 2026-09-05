<x-filament-panels::page>
@php
  $m = strtolower($r->metodo ?? '');
  $metodoLabel = ['efectivo'=>'Efectivo','transferencia'=>'Transferencia','tarjeta'=>'Tarjeta','deposito'=>'Depósito','cheque'=>'Cheque','otro'=>'Otro'][$m] ?? ucfirst($m);
  $disco = \Illuminate\Support\Facades\Storage::disk('public');
@endphp

<div style="display:flex; flex-direction:column; gap:16px;">

  {{-- Encabezado --}}
  <div style="background:#fff; border:1px solid #e7e9ed; border-radius:12px; padding:20px;">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px;">
      <div>
        <div style="font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:#8a929c;">Recibo de pago</div>
        <div style="font-size:22px; font-weight:bold; color:#161921;">{{ $r->folio ?: ('#'.$r->id) }}</div>
        <div style="color:#5b6470; font-size:13px; margin-top:4px;">
          {{ \Illuminate\Support\Carbon::parse($r->fecha)->format('d/m/Y') }}
          @if($r->pedido) · Pedido: <a href="{{ \App\Filament\Resources\PedidoEspecialResource::getUrl('view', ['record' => $r->pedido_id]) }}" style="color:#0499FC; font-weight:bold; text-decoration:none;">{{ $r->pedido->folio ?? ('#'.$r->pedido_id) }}</a>@endif
          · {{ ucfirst($r->tipo ?? 'abono') }}
        </div>
      </div>
      <div style="text-align:right;">
        <div style="font-size:24px; font-weight:bold; color:#161921;">$ {{ number_format((float)$r->monto,2) }}</div>
        @if($r->validado)
          <span style="background:#e7f7ec; color:#1f8b4c; padding:4px 12px; border-radius:20px; font-size:11px; font-weight:bold;">✓ VALIDADO</span>
        @else
          <span style="background:#fff5e6; color:#b9770e; padding:4px 12px; border-radius:20px; font-size:11px; font-weight:bold;">POR VALIDAR</span>
        @endif
      </div>
    </div>
  </div>

  {{-- Método y sus detalles --}}
  <div style="background:#fff; border:1px solid #e7e9ed; border-radius:12px; padding:20px;">
    <div style="font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:#8a929c; margin-bottom:12px;">Método de pago · {{ $metodoLabel }}</div>
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:14px;">
      @if($m === 'tarjeta')
        <div><div style="font-size:11px; color:#8a929c;">Naturaleza</div><strong>{{ $r->tarjeta_naturaleza ? ucfirst($r->tarjeta_naturaleza) : '—' }}</strong></div>
        <div><div style="font-size:11px; color:#8a929c;">Marca</div><strong>{{ $r->tipo_tarjeta ? ucfirst($r->tipo_tarjeta) : '—' }}</strong></div>
        <div><div style="font-size:11px; color:#8a929c;">Lote</div><strong>{{ $r->lote ?: '—' }}</strong></div>
      @elseif(in_array($m, ['transferencia','deposito']))
        <div><div style="font-size:11px; color:#8a929c;">N° de comprobante</div><strong>{{ $r->nro_comprobante ?: '—' }}</strong></div>
      @elseif($m === 'cheque')
        <div><div style="font-size:11px; color:#8a929c;">N° de cheque</div><strong>{{ $r->cheque_numero ?: '—' }}</strong></div>
        <div><div style="font-size:11px; color:#8a929c;">Banco</div><strong>{{ $r->cheque_banco ?: '—' }}</strong></div>
        <div><div style="font-size:11px; color:#8a929c;">Girado por</div><strong>{{ $r->cheque_girador ?: '—' }}</strong></div>
        <div><div style="font-size:11px; color:#8a929c;">Fecha de cobro</div><strong>{{ $r->cheque_fecha_cobro ? \Illuminate\Support\Carbon::parse($r->cheque_fecha_cobro)->format('d/m/Y') : '—' }}</strong></div>
      @elseif(in_array($m, ['efectivo','otro']))
        <div><div style="font-size:11px; color:#8a929c;">Recibido por</div><strong>{{ $r->recibido_por ?: '—' }}</strong></div>
      @endif
    </div>
    @if($r->nota)
      <div style="margin-top:14px; padding-top:14px; border-top:1px solid #edeff2;">
        <div style="font-size:11px; color:#8a929c;">Nota</div>{{ $r->nota }}
      </div>
    @endif
  </div>

  {{-- Pagador (si es distinto al cliente) --}}
  @if($r->pagador_nombre)
  <div style="background:#fff; border:1px solid #e7e9ed; border-radius:12px; padding:20px;">
    <div style="font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:#8a929c; margin-bottom:12px;">Quién paga</div>
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:14px;">
      <div><div style="font-size:11px; color:#8a929c;">Nombre</div><strong>{{ $r->pagador_nombre }}</strong></div>
      <div><div style="font-size:11px; color:#8a929c;">Cédula / RUC</div>{{ $r->pagador_id_num ?: '—' }}</div>
      <div><div style="font-size:11px; color:#8a929c;">Teléfono</div>{{ $r->pagador_contacto ?: '—' }}</div>
      <div><div style="font-size:11px; color:#8a929c;">Email</div>{{ $r->pagador_email ?: '—' }}</div>
    </div>
  </div>
  @endif

  {{-- Comprobantes (fotos) --}}
  @if(!empty($comps))
  <div style="background:#fff; border:1px solid #e7e9ed; border-radius:12px; padding:20px;">
    <div style="font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:#8a929c; margin-bottom:12px;">Comprobantes</div>
    <div style="display:flex; flex-wrap:wrap; gap:12px;">
      @foreach($comps as $cp)
        @php $url = $disco->url($cp); @endphp
        <a href="{{ $url }}" target="_blank" style="display:block;">
          <img src="{{ $url }}" style="height:160px; width:160px; object-fit:cover; border-radius:10px; border:1px solid #e4e8ec;" />
        </a>
      @endforeach
    </div>
    <div style="font-size:12px; color:#8a929c; margin-top:8px;">Clic en una imagen para verla en grande.</div>
  </div>
  @endif

</div>
</x-filament-panels::page>
