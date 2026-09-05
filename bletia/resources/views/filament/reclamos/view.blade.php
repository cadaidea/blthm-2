<x-filament-panels::page>
<div style="display:flex; flex-direction:column; gap:16px;">

    {{-- Cabecera: folio + estado --}}
    <div style="background:#fff; border:1px solid #edeff2; border-radius:14px; padding:18px;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
            <div>
                <div style="font-size:12px; text-transform:uppercase; letter-spacing:.5px; color:#8a929c;">Reclamo / Garantía</div>
                <div style="font-size:22px; font-weight:bold; color:#161921;">{{ $r->folio ?: ('#'.$r->id) }}</div>
                <div style="color:#8a929c; font-size:12px;">{{ $r->created_at?->format('d/m/Y H:i') }}</div>
            </div>
            @php
                $estLabel = ['abierto'=>'Abierto','en_revision'=>'En revisión','en_reparacion'=>'En reparación','resuelto'=>'Resuelto','rechazado'=>'Rechazado'][$r->estado] ?? $r->estado;
                $estColor = ['abierto'=>'#b8860b','en_revision'=>'#0499FC','en_reparacion'=>'#161921','resuelto'=>'#1f8b4c','rechazado'=>'#c0392b'][$r->estado] ?? '#5b6470';
            @endphp
            <span style="background:{{ $estColor }}1a; color:{{ $estColor }}; padding:8px 18px; border-radius:999px; font-weight:bold; font-size:14px;">{{ $estLabel }}</span>
        </div>
    </div>

    {{-- Origen del reclamo --}}
    <div style="background:#fff; border:1px solid #edeff2; border-radius:14px; padding:18px;">
        <div style="font-size:12px; text-transform:uppercase; letter-spacing:.5px; color:#8a929c; margin-bottom:12px;">Origen</div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px;">
            <div>
                <div style="color:#8a929c; font-size:12px;">Cliente</div>
                <div style="font-weight:bold;">{{ $r->cliente->nombre ?? '—' }}</div>
            </div>
            <div>
                <div style="color:#8a929c; font-size:12px;">Pedido</div>
                @if(!empty($hist['folio']) && !empty($hist['pedido_id']))
                    <a href="{{ \App\Filament\Resources\PedidoEspecialResource::getUrl('view', ['record' => $hist['pedido_id']]) }}" target="_blank" style="color:#0499FC; font-weight:bold; text-decoration:none;">{{ $hist['folio'] }}</a>
                @else
                    <div style="font-weight:bold;">{{ $hist['folio'] ?? '—' }}</div>
                @endif
            </div>
            <div>
                <div style="color:#8a929c; font-size:12px;">Producto</div>
                <div style="font-weight:bold;">{{ $r->producto ?: '—' }}</div>
            </div>
            <div>
                <div style="color:#8a929c; font-size:12px;">Tipo de problema</div>
                <div style="font-weight:bold;">{{ $r->tipo_problema ? ucfirst($r->tipo_problema) : '—' }}</div>
            </div>
            @if(!empty($hist['nro_factura']))
            <div>
                <div style="color:#8a929c; font-size:12px;">Factura</div>
                <div style="font-weight:bold;">{{ $hist['nro_factura'] }}</div>
            </div>
            @endif
            @if(!empty($hist['fecha_entrega']))
            <div>
                <div style="color:#8a929c; font-size:12px;">Fecha de entrega</div>
                <div style="font-weight:bold;">{{ \Illuminate\Support\Carbon::parse($hist['fecha_entrega'])->format('d/m/Y') }}</div>
            </div>
            @endif
        </div>
    </div>

    {{-- Trazabilidad del pedido (solo lectura, con enlaces) --}}
    @if(!empty($hist))
    <div style="background:#f8f9fb; border:1px solid #edeff2; border-radius:14px; padding:18px;">
        <div style="font-size:12px; text-transform:uppercase; letter-spacing:.5px; color:#8a929c; margin-bottom:12px;">Historial del pedido <span style="color:#c0c8d4; font-weight:normal;">(solo lectura)</span></div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px;">
            <div>
                <div style="color:#8a929c; font-size:12px;">Vendió</div>
                <div style="font-weight:bold;">{{ $hist['vendedor'] ?? '—' }}</div>
            </div>
            <div>
                <div style="color:#8a929c; font-size:12px;">Aprobó</div>
                <div style="font-weight:bold;">{{ $hist['aprobado_por'] ?? '—' }}</div>
            </div>
            <div>
                <div style="color:#8a929c; font-size:12px;">Fabricó</div>
                <div style="font-weight:bold;">
                    {{ $hist['fabricante'] ?? '—' }}
                    @if(!empty($hist['fabricante_tipo']))
                        <span style="background:#f4f6f8; border:1px solid #e4e8ec; border-radius:5px; padding:2px 7px; font-size:11px; font-weight:normal; margin-left:4px;">{{ $hist['fabricante_tipo'] === 'proveedor' ? 'Proveedor' : 'Taller' }}</span>
                    @endif
                </div>
                @if(!empty($hist['proveedor']))
                    <div style="color:#8a929c; font-size:12px; margin-top:2px;">{{ $hist['proveedor']->email ?? '' }}</div>
                @endif
            </div>
            <div>
                <div style="color:#8a929c; font-size:12px;">Trasladó / retiró</div>
                <div style="font-weight:bold;">{{ $hist['traslado'] ?? '—' }}</div>
                @if(!empty($hist['transportista_cel']))
                    <div style="color:#8a929c; font-size:12px;">{{ $hist['transportista_cel'] }}</div>
                @endif
            </div>
            @if(!empty($hist['recibido_por']))
            <div>
                <div style="color:#8a929c; font-size:12px;">Recibió el producto</div>
                <div style="font-weight:bold;">{{ $hist['recibido_por'] }}</div>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Descripción --}}
    @if($r->descripcion)
    <div style="background:#fff; border:1px solid #edeff2; border-radius:14px; padding:18px;">
        <div style="font-size:12px; text-transform:uppercase; letter-spacing:.5px; color:#8a929c; margin-bottom:8px;">Descripción del problema</div>
        <div style="color:#2b3038; line-height:1.6;">{!! nl2br(e($r->descripcion)) !!}</div>
    </div>
    @endif

    {{-- Fotos --}}
    @php $fotos = is_array($r->fotos) ? $r->fotos : []; @endphp
    @if(count($fotos))
    <div style="background:#fff; border:1px solid #edeff2; border-radius:14px; padding:18px;">
        <div style="font-size:12px; text-transform:uppercase; letter-spacing:.5px; color:#8a929c; margin-bottom:12px;">Fotos del problema</div>
        <div style="display:flex; flex-wrap:wrap; gap:10px;">
            @foreach($fotos as $f)
                <a href="{{ \Illuminate\Support\Facades\Storage::url($f) }}" target="_blank">
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($f) }}" style="width:120px; height:120px; object-fit:cover; border-radius:10px; border:1px solid #e4e8ec; cursor:pointer;">
                </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Resolución --}}
    @if($r->resolucion)
    <div style="background:#f6fbf7; border:1px solid #cdeed6; border-radius:14px; padding:18px;">
        <div style="font-size:12px; text-transform:uppercase; letter-spacing:.5px; color:#1f8b4c; margin-bottom:8px;">Resolución</div>
        @php $resLabel = ['reparacion'=>'Reparación','reposicion'=>'Reposición / cambio','nota_credito'=>'Nota de crédito','reembolso'=>'Reembolso','sin_garantia'=>'Sin garantía'][$r->resolucion] ?? $r->resolucion; @endphp
        <div style="font-weight:bold; color:#161921; font-size:16px;">{{ $resLabel }}</div>
        @if($r->resolucion_nota)<div style="color:#2b3038; margin-top:6px; line-height:1.5;">{{ $r->resolucion_nota }}</div>@endif
        @if((float)$r->costo > 0)<div style="color:#8a929c; font-size:13px; margin-top:6px;">Costo interno: ${{ number_format((float)$r->costo,2) }}</div>@endif
        @if($r->resuelto_at)<div style="color:#8a929c; font-size:12px; margin-top:4px;">Resuelto el {{ \Illuminate\Support\Carbon::parse($r->resuelto_at)->format('d/m/Y') }}</div>@endif
    </div>
    @endif

</div>
</x-filament-panels::page>
