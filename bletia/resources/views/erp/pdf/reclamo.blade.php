<!DOCTYPE html><html><head><meta charset="utf-8"><style>
  * { font-family: DejaVu Sans, sans-serif; }
  body { color:#2b3038; font-size:12px; }
  .head { display:flex; justify-content:space-between; border-bottom:2px solid #161921; padding-bottom:10px; margin-bottom:14px; }
  .title { font-size:20px; font-weight:bold; color:#161921; }
  .sub { color:#8a929c; font-size:11px; }
  .box { border:1px solid #e4e8ec; border-radius:8px; padding:10px 12px; margin-bottom:10px; }
  .label { color:#8a929c; font-size:10px; text-transform:uppercase; letter-spacing:.5px; }
  .row { margin-bottom:6px; }
  table { width:100%; border-collapse:collapse; }
  td { padding:4px 8px; vertical-align:top; }
  .badge { background:#f4f6f8; border:1px solid #e4e8ec; border-radius:5px; padding:3px 8px; font-size:11px; }
  .foto { width:150px; height:150px; object-fit:cover; border:1px solid #e4e8ec; border-radius:6px; margin:4px; }
</style></head><body>
  <div class="head">
    <div>
      <div class="title">{{ $empresa['nombre'] ?? 'BLETIA' }}</div>
      <div class="sub">{{ $empresa['ruc'] ?? '' }}</div>
    </div>
    <div style="text-align:right;">
      <div class="title">RECLAMO</div>
      <div class="sub">{{ $r->folio ?: ('#'.$r->id) }} · {{ $fecha }}</div>
    </div>
  </div>

  <div class="box">
    <div class="label">Estado</div>
    @php $estLabel = ['abierto'=>'Abierto','en_revision'=>'En revisión','en_reparacion'=>'En reparación','resuelto'=>'Resuelto','rechazado'=>'Rechazado'][$r->estado] ?? $r->estado; @endphp
    <strong>{{ $estLabel }}</strong>
  </div>

  <div class="box">
    <div class="label">Cliente y origen</div>
    <table>
      <tr><td class="label">Cliente</td><td>{{ $cliente->nombre ?? '—' }}</td><td class="label">Pedido</td><td>{{ $hist['folio'] ?? '—' }}</td></tr>
      <tr><td class="label">Producto</td><td>{{ $r->producto ?: '—' }}</td><td class="label">Problema</td><td>{{ $r->tipo_problema ? ucfirst($r->tipo_problema) : '—' }}</td></tr>
      <tr><td class="label">Factura</td><td>{{ $hist['nro_factura'] ?? '—' }}</td><td class="label">Entrega</td><td>{{ $hist['fecha_entrega'] ? \Illuminate\Support\Carbon::parse($hist['fecha_entrega'])->format('d/m/Y') : '—' }}</td></tr>
    </table>
  </div>

  <div class="box">
    <div class="label">Trazabilidad del pedido</div>
    <table>
      <tr><td class="label">Vendió</td><td>{{ $hist['vendedor'] ?? '—' }}</td></tr>
      <tr><td class="label">Aprobó</td><td>{{ $hist['aprobado_por'] ?? '—' }}</td></tr>
      <tr><td class="label">Fabricó</td><td>{{ $hist['fabricante'] ?? '—' }}</td></tr>
      <tr><td class="label">Trasladó / retiró</td><td>{{ $hist['traslado'] ?? '—' }}</td></tr>
      <tr><td class="label">Recibió</td><td>{{ $hist['recibido_por'] ?? '—' }}</td></tr>
    </table>
  </div>

  @if($r->descripcion)
  <div class="box">
    <div class="label">Descripción del problema</div>
    {!! nl2br(e($r->descripcion)) !!}
  </div>
  @endif

  @if($r->resolucion)
  <div class="box">
    <div class="label">Resolución</div>
    @php $resLabel = ['reparacion'=>'Reparación','reposicion'=>'Reposición/cambio','nota_credito'=>'Nota de crédito','reembolso'=>'Reembolso','sin_garantia'=>'Sin garantía'][$r->resolucion] ?? $r->resolucion; @endphp
    <strong>{{ $resLabel }}</strong>
    @if($r->resolucion_nota)<div>{{ $r->resolucion_nota }}</div>@endif
  </div>
  @endif

  @if(count($fotos))
  <div class="box">
    <div class="label">Fotos del problema</div>
    @foreach($fotos as $f)<img src="{{ $f }}" class="foto">@endforeach
  </div>
  @endif
</body></html>
