<x-filament-panels::page>
@php
    $fmt = fn($x) => $x ? \Illuminate\Support\Carbon::parse($x)->format('d/m/Y') : '—';
@endphp
<div style="font-family:sans-serif">

    {{-- ALERTA SALDO --}}
    @if($saldo > 0)
        <div style="background:#fde8e8;border:1px solid #f5b5b5;color:#b42318;padding:14px 16px;border-radius:14px;margin-bottom:14px;font-weight:600">
            ⚠ Saldo pendiente de ${{ number_format($saldo,2) }} — no se puede entregar hasta cobrar y validar el pago.
        </div>
    @endif

    {{-- CABECERA --}}
    <div style="background:#fff;border:1px solid #eee;border-radius:14px;padding:18px;margin-bottom:14px">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
            <div>
                <div style="font-size:1.25rem;font-weight:700;color:#161921">{{ $d->folio ?: ('#'.$d->id) }}</div>
                <div style="color:#888;font-size:.85rem">Pedido {{ $p->folio ?? ('#'.$d->pedido_id) }} @if($p && $p->nro_factura) · Factura {{ $p->nro_factura }}@endif</div>
            </div>
            <span style="background:{{ $esRetiro ? '#fff4e5' : '#e7f0ff' }};color:{{ $esRetiro ? '#8a5a00' : '#1456b8' }};padding:6px 14px;border-radius:999px;font-weight:600">
                {{ $esRetiro ? 'Retiro en local' : 'Entrega a domicilio' }}
            </span>
        </div>
    </div>

    {{-- A QUIÉN / DÓNDE --}}
    {{-- SEGUIMIENTO --}}
    <div style="background:#fff;border:1px solid #eee;border-radius:14px;padding:18px;margin-bottom:14px">
        <div style="font-weight:600;color:#161921;margin-bottom:14px">Seguimiento del despacho</div>
        @php
            $d = $record;
            $esRetiroLocal = $d->ruta !== 'transportista';
            // 1) Listo: automático. Si el despacho existe, el producto ya está listo (taller o proveedor lo confirmó).
            $entregado = $d->estado === 'entregado';
            // 2) En ruta: hay transportista/conductor asignado (se despachó) — no aplica a retiro local
            $enRuta = ! $esRetiroLocal && (filled($d->conductor_nombre) || filled($d->transportista_id));
            $pasos = [];
            $pasos[] = ['ok'=>true, 'titulo'=>'Listo para despacho', 'detalle'=>'Producto terminado · '.\Illuminate\Support\Carbon::parse($d->created_at)->format('d/m/Y H:i').($d->fecha_programada ? ' · Despacho: '.\Illuminate\Support\Carbon::parse($d->fecha_programada)->format('d/m/Y') : '')];
            if ($esRetiroLocal) {
                $pasos[] = ['ok'=>$entregado, 'titulo'=>'Retirado por el cliente', 'detalle'=>$entregado ? ('El '.\Illuminate\Support\Carbon::parse($d->entregado_at)->format('d/m/Y H:i').($d->recibido_nombre ? ' · Retiró: '.$d->recibido_nombre : '')) : 'Pendiente de retiro en local'];
            } else {
                $pasos[] = ['ok'=>($enRuta || $entregado), 'titulo'=>'En ruta de entrega', 'detalle'=>($enRuta || $entregado) ? ('Transportista: '.($d->conductor_nombre ?: 'asignado').($d->placa ? ' · Placa '.$d->placa : '')) : 'Pendiente de despachar (genera la guía)'];
                $pasos[] = ['ok'=>$entregado, 'titulo'=>'Entregado', 'detalle'=>$entregado ? ('El '.\Illuminate\Support\Carbon::parse($d->entregado_at)->format('d/m/Y H:i').($d->recibido_nombre ? ' · Recibió: '.$d->recibido_nombre : '')) : 'Pendiente de entrega'];
            }
        @endphp
        <div style="display:flex;flex-direction:column;gap:0">
            @foreach($pasos as $i => $paso)
            <div style="display:flex;gap:12px;align-items:flex-start">
                <div style="display:flex;flex-direction:column;align-items:center">
                    <div style="width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:bold;color:#fff;background:{{ $paso['ok'] ? '#2e9e6b' : '#cfd4da' }}">{{ $paso['ok'] ? '✓' : ($i+1) }}</div>
                    @if(!$loop->last)<div style="width:2px;height:28px;background:{{ $paso['ok'] ? '#2e9e6b' : '#e7e9ed' }}"></div>@endif
                </div>
                <div style="padding-bottom:14px">
                    <div style="font-weight:600;color:{{ $paso['ok'] ? '#161921' : '#8a929c' }}">{{ $paso['titulo'] }}</div>
                    <div style="font-size:13px;color:#5b6470">{{ $paso['detalle'] }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div style="background:#fff;border:1px solid #eee;border-radius:14px;padding:18px;margin-bottom:14px">
        <div style="font-weight:600;color:#161921;margin-bottom:10px">{{ $esRetiro ? 'Retiro' : 'Envío' }}</div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;color:#444">
            <div><strong>Cliente:</strong> {{ $cliente->nombre ?? '—' }}</div>
            <div><strong>Contacto:</strong> {{ trim(($cliente->celular ?? $cliente->telefono ?? '').' '.($cliente->email ?? ''), ' ') ?: '—' }}</div>
            <div><strong>Identificación:</strong> {{ $cliente->cedula_ruc ?? $cliente->identificacion ?? '—' }}</div>
            @if($esRetiro)
                <div><strong>Local de retiro:</strong> {{ $localRetiro ?? '—' }}</div>
            @else
                <div style="grid-column:1/-1"><strong>Dirección:</strong> {{ trim(($p->direccion_envio ?? '').' · '.($p->ciudad_envio ?? '').' · '.($p->contacto_envio ?? ''), ' ·') ?: '—' }}</div>
                <div><strong>Transportista:</strong> {{ $transportista ?? '—' }}</div>
                @if($d->conductor_nombre)<div><strong>Conductor:</strong> {{ $d->conductor_nombre }} {{ $d->placa ? '· '.$d->placa : '' }}</div>@endif
            @endif
            <div><strong>Fecha programada:</strong> {{ $fmt($d->fecha_programada) }}</div>
        </div>
    </div>

    {{-- CONTENIDO A VERIFICAR --}}
    <div style="background:#fff;border:1px solid #eee;border-radius:14px;padding:18px;margin-bottom:14px">
        <div style="font-weight:600;color:#161921;margin-bottom:10px">Contenido a {{ $esRetiro ? 'entregar' : 'enviar' }} — verifica que coincida</div>
        @forelse($items as $it)
            <div style="border:1px solid #eee;border-radius:10px;padding:10px 12px;margin-bottom:8px">
                <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:6px">
                    <strong>{{ $it->nombre }} <span style="color:#888">x{{ $it->cantidad }}</span></strong>
                    @if(isset($it->bultos) && $it->bultos)<span style="color:#666;font-size:.85rem">{{ $it->bultos }} bulto(s)</span>@endif
                </div>
                @if($it->variantes)<div style="color:#0499FC;font-size:.9rem;margin-top:3px">{{ $it->variantes }}</div>@endif
                @php($specs = collect(['tapiz_principal'=>'Tapiz','tapiz_secundario'=>'Tapiz sec.','cojines'=>'Cojines','lacado'=>'Lacado'])->filter(fn($l,$k)=>filled($it->$k ?? null)))
                @if($specs->count())
                    <div style="color:#666;font-size:.85rem;margin-top:3px">
                        @foreach($specs as $k=>$label){{ $label }}: {{ $it->$k }}@if(!$loop->last) · @endif @endforeach
                    </div>
                @endif
            </div>
        @empty
            <div style="color:#999">Sin ítems.</div>
        @endforelse
    </div>

    {{-- TRAZABILIDAD CORTA --}}
    <div style="background:#fff;border:1px solid #eee;border-radius:14px;padding:18px">
        <div style="font-weight:600;color:#161921;margin-bottom:10px">Responsables</div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px;color:#444">
            <div><strong>Vendido por:</strong> {{ $vendidoPor ?? '—' }}</div>
            <div><strong>Aprobado por:</strong> {{ $aprobadoPor ?? '—' }}</div>
            <div><strong>Fabricado en:</strong> {{ ($p->destino_fab ?? '') === 'interno' ? 'Taller propio' : 'Proveedor externo' }}</div>
        </div>
        @if($d->estado === 'entregado')
            <div style="margin-top:12px;padding-top:10px;border-top:1px solid #f0f0f0;color:#1c7a4a;font-weight:600">
                ✓ Entregado a {{ $d->recibido_nombre ?? '—' }} {{ $d->entregado_at ? '· '.\Illuminate\Support\Carbon::parse($d->entregado_at)->format('d/m/Y H:i') : '' }}
            </div>
        @endif
    </div>
</div>
</x-filament-panels::page>
