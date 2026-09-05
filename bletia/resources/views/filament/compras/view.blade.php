<x-filament-panels::page>
<div style="display:flex; flex-direction:column; gap:16px;">

    <div style="background:#fff; border:1px solid #edeff2; border-radius:14px; padding:18px;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
            <div>
                <div style="font-size:12px; text-transform:uppercase; letter-spacing:.5px; color:#8a929c;">{{ $c->tipo === 'proveedor' ? 'Compra a proveedor' : 'Orden de producción' }}</div>
                <div style="font-size:22px; font-weight:bold; color:#161921;">{{ $c->folio ?: ('#'.$c->id) }}</div>
                <div style="color:#8a929c; font-size:12px;">{{ $c->created_at?->format('d/m/Y H:i') }}</div>
            </div>
            @php
                $estLabel = ['creada'=>'Creada','en_proceso'=>'En proceso','listo_envio'=>'Listo para enviar','en_transito'=>'En tránsito','recibida'=>'Recibida','anulada'=>'Anulada'][$c->estado] ?? $c->estado;
                $estColor = ['creada'=>'#5b6470','en_proceso'=>'#b8860b','listo_envio'=>'#0499FC','en_transito'=>'#534AB7','recibida'=>'#1f8b4c','anulada'=>'#c0392b'][$c->estado] ?? '#5b6470';
            @endphp
            <span style="background:{{ $estColor }}1a; color:{{ $estColor }}; padding:8px 18px; border-radius:999px; font-weight:bold; font-size:14px;">{{ $estLabel }}</span>
        </div>
    </div>

    <div style="background:#fff; border:1px solid #edeff2; border-radius:14px; padding:18px;">
        <div style="font-size:12px; text-transform:uppercase; letter-spacing:.5px; color:#8a929c; margin-bottom:12px;">Origen y destino</div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px;">
            @if($c->tipo === 'proveedor')
            <div>
                <div style="color:#8a929c; font-size:12px;">Proveedor</div>
                <div style="font-weight:bold;">{{ $c->proveedor->nombre ?? '—' }}</div>
            </div>
            @endif
            <div>
                <div style="color:#8a929c; font-size:12px;">Destino</div>
                <div style="font-weight:bold;">{{ $c->localDestino->nombre ?? '—' }}</div>
            </div>
            @if($c->doc_tipo)
            <div>
                <div style="color:#8a929c; font-size:12px;">Documento</div>
                <div style="font-weight:bold;">{{ ['factura'=>'Factura','nota_venta'=>'Nota de venta','ninguno'=>'Sin documento'][$c->doc_tipo] ?? $c->doc_tipo }} {{ $c->doc_numero }}</div>
            </div>
            @endif
        </div>
    </div>

    <div style="background:#fff; border:1px solid #edeff2; border-radius:14px; padding:18px;">
        <div style="font-size:12px; text-transform:uppercase; letter-spacing:.5px; color:#8a929c; margin-bottom:12px;">Ítems</div>
        <table style="width:100%; border-collapse:collapse; font-size:13px;">
            <thead><tr style="border-bottom:1px solid #edeff2; text-align:left; color:#8a929c;">
                <th style="padding:6px 0;">Producto</th><th style="text-align:right;">Cant.</th><th style="text-align:right;">Costo u.</th><th style="text-align:right;">Subtotal</th>
            </tr></thead>
            <tbody>
                @foreach($c->items as $it)
                @php
                    $fotoPath = optional($it->variante)->foto ?: optional($it->producto)->imagen_principal;
                    $foto = $fotoPath ? \Illuminate\Support\Facades\Storage::disk('public')->url($fotoPath) : null;
                @endphp
                <tr style="border-bottom:1px solid #f4f6f8;">
                    <td style="padding:8px 0;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            @if($foto)
                                <img src="{{ $foto }}" style="width:40px;height:40px;object-fit:cover;border-radius:6px;border:1px solid #e4e8ec;">
                            @endif
                            <div>
                                <div>{{ $it->nombre }}</div>
                                @if($it->variante)
                                    <div style="color:#8a929c;font-size:11px;">{{ $it->variante->combo_label ?? $it->variante->nombre }}</div>
                                @endif
                                @php
                                    $acab = collect([
                                        $it->tapiz_principal ? 'Tapiz: ' . $it->tapiz_principal : null,
                                        $it->tapiz_secundario ? 'Tapiz 2: ' . $it->tapiz_secundario : null,
                                        $it->cojines ? 'Cojines: ' . $it->cojines : null,
                                        $it->lacado ? 'Lacado: ' . $it->lacado : null,
                                    ])->filter()->implode(' · ');
                                @endphp
                                @if($acab)
                                    <div style="color:#8a929c;font-size:11px;">{{ $acab }}</div>
                                @endif
                                @if($it->notas_adicionales)
                                    <div style="color:#b45309;font-size:11px;">Nota: {{ $it->notas_adicionales }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td style="text-align:right;">{{ $it->cantidad }}</td>
                    <td style="text-align:right;">$ {{ number_format($it->costo_unitario,2) }}</td>
                    <td style="text-align:right; font-weight:bold;">$ {{ number_format($it->subtotal,2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div style="margin-top:12px; text-align:right; font-size:13px;">
            <div>Subtotal: $ {{ number_format($c->subtotal,2) }}</div>
            <div>IVA: $ {{ number_format($c->iva,2) }}</div>
            <div style="font-size:18px; font-weight:bold; color:#161921;">Total: $ {{ number_format($c->total,2) }}</div>
        </div>
    </div>

    <div style="background:#fff; border:1px solid #edeff2; border-radius:14px; padding:18px;">
        <div style="font-size:12px; text-transform:uppercase; letter-spacing:.5px; color:#8a929c; margin-bottom:12px;">Pagos al proveedor</div>
        @forelse($c->pagos as $p)
            <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid #f4f6f8; font-size:13px;">
                <span>{{ ucfirst($p->metodo) }} · {{ $p->fecha?->format('d/m/Y') }}</span>
                <strong>$ {{ number_format($p->monto,2) }}</strong>
            </div>
        @empty
            <div style="color:#8a929c; font-size:13px;">Sin pagos registrados.</div>
        @endforelse
        <div style="margin-top:10px; padding-top:10px; border-top:1px solid #edeff2; display:flex; justify-content:space-between; font-size:14px;">
            <span>Saldo pendiente</span>
            <strong style="color:{{ $c->saldo() > 0 ? '#c0392b' : '#1f8b4c' }};">$ {{ number_format($c->saldo(),2) }}</strong>
        </div>
    </div>

    @if($c->tipo === 'produccion_interna')
    <div style="background:#fff; border:1px solid #edeff2; border-radius:14px; padding:18px;">
        <div style="font-size:12px; text-transform:uppercase; letter-spacing:.5px; color:#8a929c; margin-bottom:12px;">Materiales usados (taller)</div>
        @forelse($materiales as $m)
            @php
                $tipoLabel = ['solicitud'=>'Solicitado','entrega'=>'Entregado','uso'=>'Usado','devolucion'=>'Devuelto'][$m->tipo] ?? ucfirst($m->tipo);
                $tipoColor = ['solicitud'=>'#b8860b','entrega'=>'#1f8b4c','uso'=>'#c0392b','devolucion'=>'#5b6470'][$m->tipo] ?? '#5b6470';
            @endphp
            <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid #f4f6f8; font-size:13px;">
                <span>{{ $m->materia->nombre ?? '—' }} · {{ number_format((float) $m->cantidad, 2) }} {{ $m->materia->unidad ?? '' }}</span>
                <span style="background:{{ $tipoColor }}1a; color:{{ $tipoColor }}; padding:3px 10px; border-radius:999px; font-size:12px; font-weight:bold;">
                    {{ $tipoLabel }}
                </span>
            </div>
        @empty
            <div style="color:#8a929c; font-size:13px;">Sin solicitudes de material aún.</div>
        @endforelse
        @if($costoMaterialReal > 0)
        <div style="margin-top:10px; padding-top:10px; border-top:1px solid #edeff2; display:flex; justify-content:space-between; font-size:14px;">
            <span>Costo real de material entregado</span>
            <strong>$ {{ number_format($costoMaterialReal, 2) }}</strong>
        </div>
        @endif
    </div>
    @endif
    @if($c->notas)
    <div style="background:#fff; border:1px solid #edeff2; border-radius:14px; padding:18px;">
        <div style="font-size:12px; text-transform:uppercase; letter-spacing:.5px; color:#8a929c; margin-bottom:8px;">Notas</div>
        <div>{!! nl2br(e($c->notas)) !!}</div>
    </div>
    @endif

</div>
</x-filament-panels::page>
