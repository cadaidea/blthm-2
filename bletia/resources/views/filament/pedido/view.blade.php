<x-filament-panels::page>
@php
    $estadoColores = [
        'pendiente'=>'#e6862e','por_aprobar'=>'#e6862e','aprobado'=>'#0499FC',
        'enviado_proveedor'=>'#0499FC','en_fabricacion'=>'#0499FC','en_produccion'=>'#7a5af8',
        'listo_proveedor'=>'#0499FC','en_bodega'=>'#3d8b8b','listo_despacho'=>'#2e9e6b',
        'despachado'=>'#2e9e6b','entregado'=>'#2e9e6b','anulado'=>'#d9534f','cancelado'=>'#d9534f',
    ];
    $col = $estadoColores[$p->estado_erp] ?? '#888';
    $fmt = fn($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d/m/Y') : '—';
    $img = fn($path) => $path ? \Illuminate\Support\Facades\Storage::disk('public')->url($path) : null;
@endphp

<div style="font-family:sans-serif" x-data="{ lb:false, lbSrc:'', lbCap:'' }">
    {{-- CABECERA --}}
    <div style="background:#fff;border:1px solid #eee;border-radius:14px;padding:18px;margin-bottom:14px">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
            <div>
                <div style="font-size:1.3rem;font-weight:700;color:#161921">{{ $p->folio ?: ('#'.$p->id) }}</div>
                <div style="color:#888;font-size:.85rem">{{ ucfirst($p->forma_venta ?? $p->tipo_erp ?? 'local') }} · creado {{ $fmt($p->created_at) }}</div>
            </div>
            <span style="background:{{ $col }}1a;color:{{ $col }};padding:6px 14px;border-radius:999px;font-weight:600">
                {{ $estados[$p->estado_erp] ?? $p->estado_erp }}
            </span>
        </div>
        <div style="display:flex;gap:24px;margin-top:14px;flex-wrap:wrap;color:#444">
            <div><strong>Solicitada:</strong> {{ $fmt($p->fecha_solicitada) }}</div>
            <div><strong>Comprometida:</strong> {{ $fmt($p->fecha_comprometida) }}</div>
            @if($p->folio_of)<div><strong>OF:</strong> {{ $p->folio_of }}</div>@endif
        </div>
    </div>

    {{-- CLIENTE Y ENTREGA --}}
    <div style="background:#fff;border:1px solid #eee;border-radius:14px;padding:18px;margin-bottom:14px">
        <div style="font-weight:600;color:#161921;margin-bottom:10px">Cliente y entrega</div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;color:#444">
            <div><strong>Cliente:</strong> {{ $p->cliente->nombre ?? '—' }}</div>
            <div><strong>Contacto:</strong> {{ trim(($p->cliente->celular ?? $p->cliente->telefono ?? '').' '.($p->cliente->email ?? ''), ' ') ?: '—' }}</div>
            <div><strong>Identificación:</strong> {{ $p->cliente->cedula_ruc ?? $p->cliente->identificacion ?? '—' }}</div>
            <div style="grid-column:1/-1"><strong>Entrega:</strong>
                @if($p->retira_local) Retira en local
                @else Envío a domicilio — {{ trim(($p->direccion_envio ?? '').' · '.($p->ciudad_envio ?? '').' · '.($p->contacto_envio ?? ''), ' ·') ?: '—' }}
                @endif
            </div>
        </div>
    </div>

    {{-- ÍTEMS --}}
    <div style="background:#fff;border:1px solid #eee;border-radius:14px;padding:18px;margin-bottom:14px">
        <div style="font-weight:600;color:#161921;margin-bottom:10px">Lo que pidió el cliente ({{ $p->items->count() }} ítem/s)</div>
        @foreach($p->items as $it)
            <div style="border:1px solid #eee;border-radius:10px;padding:12px;margin-bottom:10px">
                <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:6px">
                    <strong>{{ $it->nombre }} <span style="color:#888">x{{ $it->cantidad }}</span></strong>
                    <span style="font-weight:700">${{ number_format((float)$it->subtotal,2) }}</span>
                </div>
                @if($it->variantes)<div style="color:#0499FC;margin-top:4px">{{ $it->variantes }}</div>@endif
                @php($specs = collect(['tapiz_principal'=>'Tapiz','tapiz_secundario'=>'Tapiz sec.','cojines'=>'Cojines','cojines_secundario'=>'Cojines sec.','lacado'=>'Lacado'])->filter(fn($l,$k)=>filled($it->$k)))
                @if($specs->count())
                    <ul style="margin:6px 0;padding-left:18px;color:#555">
                        @foreach($specs as $k=>$label)<li>{{ $label }}: {{ $it->$k }}</li>@endforeach
                    </ul>
                @endif
                @if($it->descuento_pct || $it->valor_adicional)
                    <div style="color:#666;font-size:.85rem;margin-top:4px">
                        PVP base ${{ number_format((float)($it->pvp_base ?: $it->precio),2) }}
                        @if($it->descuento_pct) · Desc {{ $it->descuento_pct }}%@endif
                        @if($it->valor_adicional) · Adicional ${{ number_format((float)$it->valor_adicional,2) }} ({{ $it->motivo_adicional }})@endif
                    </div>
                @endif
                @if($it->notas_adicionales)<div style="color:#666;margin-top:4px">Notas: {{ $it->notas_adicionales }}</div>@endif
                @php($lbl = function($tipo,$val){ return $val ? $tipo.': '.$val : $tipo; })
                @php($fotos = collect([
                    ['p'=>$it->foto_modelo,'l'=>$lbl('Modelo', $it->nombre)],
                    ['p'=>$it->foto_tapiz_principal,'l'=>$lbl('Tapiz principal', $it->tapiz_principal)],
                    ['p'=>$it->foto_tapiz_secundario,'l'=>$lbl('Tapiz secundario', $it->tapiz_secundario)],
                    ['p'=>$it->foto_cojines,'l'=>$lbl('Cojines', $it->cojines)],
                    ['p'=>$it->foto_cojines_secundario,'l'=>$lbl('Cojines secundario', $it->cojines_secundario)],
                    ['p'=>$it->foto_lacado,'l'=>$lbl('Lacado', $it->lacado)],
                    ['p'=>$it->foto_adicional,'l'=>$lbl('Adicional', $it->motivo_adicional)],
                ])->filter(fn($f)=>filled($f['p'])))
                @if($fotos->count())
                    <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:10px">
                        @foreach($fotos as $f)
                            <div style="text-align:center">
                                <img src="{{ $img($f['p']) }}" @click="lb=true; lbSrc='{{ $img($f['p']) }}'; lbCap='{{ $f['l'] }}'"
                                     style="height:84px;width:84px;object-fit:cover;border-radius:8px;border:1px solid #eee;cursor:pointer" />
                                <div style="font-size:.72rem;color:#777;margin-top:3px">{{ $f['l'] }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
        <div style="text-align:right;margin-top:8px;font-size:1.05rem"><strong>Total: ${{ number_format((float)$p->total,2) }}</strong></div>
    </div>

    {{-- PAGOS --}}
    <div style="background:#fff;border:1px solid #eee;border-radius:14px;padding:18px;margin-bottom:14px">
        <div style="font-weight:600;color:#161921;margin-bottom:10px">Pagos</div>
        <div style="color:#444;margin-bottom:8px">
            Pagado <strong style="color:#2e9e6b">${{ number_format($pagado,2) }}</strong>
            · Saldo <strong style="color:{{ $saldo>0 ? '#d9534f':'#2e9e6b' }}">${{ number_format($saldo,2) }}</strong>
        </div>
        @forelse($recibos as $r)
            @php($comps = $r->comprobantes ? (is_array($r->comprobantes) ? $r->comprobantes : json_decode($r->comprobantes, true)) : [])
            <div style="border-top:1px solid #f0f0f0;padding:10px 0">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;color:#555">
                    <span>{{ $r->folio ?? '—' }} · {{ ucfirst($r->metodo ?? '—') }}
                        @if($r->validado)<span style="color:#2e9e6b;font-weight:600">✓ validado</span>
                        @else<span style="color:#e6862e;font-weight:600">por validar</span>@endif
                    </span>
                    <span style="font-weight:700">${{ number_format((float)$r->monto,2) }}</span>
                </div>
                <div style="display:flex;gap:10px;margin-top:8px;flex-wrap:wrap">
                    @if(!empty($comps))
                        @foreach($comps as $cp)
                            <img src="{{ $img($cp) }}" @click="lb=true; lbSrc='{{ $img($cp) }}'; lbCap='Comprobante {{ $r->folio ?? '' }}'"
                                 style="height:64px;width:64px;object-fit:cover;border-radius:8px;border:1px solid #eee;cursor:pointer" />
                        @endforeach
                    @endif
                    @if($puedeValidar && ! $r->validado)
                        <button wire:click="validarPago({{ $r->id }})" wire:loading.attr="disabled"
                            style="background:#2e9e6b;color:#fff;border:none;padding:8px 16px;border-radius:999px;font-weight:600;cursor:pointer;height:36px;align-self:center">
                            Validar pago
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div style="color:#999">Sin pagos registrados.</div>
        @endforelse
    </div>

    {{-- TRAZABILIDAD --}}
    <div style="background:#fff;border:1px solid #eee;border-radius:14px;padding:18px">
        <div style="font-weight:600;color:#161921;margin-bottom:10px">Trazabilidad</div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px;color:#444;margin-bottom:12px">
            <div><strong>Vendido por:</strong> {{ $vendidoPor ?? '—' }}</div>
            <div><strong>Aprobado por:</strong> {{ $aprobadoPor ?? '—' }}</div>
            <div><strong>Se fabrica en:</strong> {{ $fabricaEn }}</div>
        </div>
        <div style="border-top:1px solid #f0f0f0;padding-top:10px">
            @forelse($p->historial as $h)
                <div style="display:flex;gap:10px;padding:5px 0;color:#555;font-size:.9rem">
                    <span style="color:#999;min-width:120px">{{ \Illuminate\Support\Carbon::parse($h->created_at)->format('d/m/Y H:i') }}</span>
                    <span><strong>{{ \App\Models\PedidoHistorial::ETIQUETAS[$h->accion] ?? $h->accion }}</strong>
                        {{ $h->user_nombre ? '· '.$h->user_nombre : '' }} {{ $h->rol ? '('.$h->rol.')' : '' }}
                        {{ $h->nota ? '— '.$h->nota : '' }}</span>
                </div>
            @empty
                <div style="color:#999">Sin movimientos registrados.</div>
            @endforelse
        </div>
    </div>

    {{-- LIGHTBOX POPUP --}}
    <div x-show="lb" x-cloak @click="lb=false" @keydown.escape.window="lb=false"
         style="position:fixed;inset:0;background:rgba(0,0,0,.8);display:flex;align-items:center;justify-content:center;z-index:9999;flex-direction:column;gap:14px;padding:20px">
        <img :src="lbSrc" style="max-width:92vw;max-height:80vh;border-radius:10px;box-shadow:0 10px 40px rgba(0,0,0,.5)" />
        <div x-text="lbCap" style="color:#fff;font-weight:600;font-size:1.05rem"></div>
        <div style="color:#bbb;font-size:.8rem">Toca fuera o Esc para cerrar</div>
    </div>
</div>
</x-filament-panels::page>
