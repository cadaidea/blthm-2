<x-filament-panels::page>
    @php($r = $record)
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
        <div style="background:#161921;color:#fff;padding:1rem 1.25rem;display:flex;justify-content:space-between;flex-wrap:wrap;gap:.5rem">
            <div>
                <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;opacity:.7">Rol de pagos {{ $r->folio }}</div>
                <div style="font-size:1.15rem;font-weight:700">{{ $r->empleado?->nombre }}</div>
                <div style="font-size:.85rem;opacity:.8">{{ $r->nombreMes() }} {{ $r->anio }} · {{ $r->relacion === 'honorarios' ? 'Honorarios' : 'Relación de dependencia' }}</div>
            </div>
            <span style="align-self:flex-start;padding:.2rem .7rem;border-radius:999px;font-size:.75rem;font-weight:600;background:rgba(255,255,255,.15)">{{ ucfirst($r->estado) }}</span>
        </div>

        <div style="padding:1.25rem;display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">
            <div>
                <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:#8a949e;font-weight:600;margin-bottom:.5rem">Ingresos</div>
                @foreach(['sueldo'=>'Sueldo','horas_extra'=>'Horas extra','comisiones'=>'Comisiones','bonos'=>'Bonos','otros_ingresos'=>'Otros'] as $k=>$lbl)
                    @if((float)$r->$k > 0)<div style="display:flex;justify-content:space-between;padding:.2rem 0;font-size:.88rem"><span style="color:#475569">{{ $lbl }}</span><span>${{ number_format($r->$k,2) }}</span></div>@endif
                @endforeach
                <div style="display:flex;justify-content:space-between;padding:.4rem 0;border-top:1px solid #f1f5f9;font-weight:700"><span>Total ingresos</span><span>${{ number_format($r->total_ingresos,2) }}</span></div>
            </div>
            <div>
                <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:#8a949e;font-weight:600;margin-bottom:.5rem">Descuentos</div>
                @foreach(['aporte_personal'=>'Aporte IESS (9,45%)','anticipos'=>'Anticipos','prestamos_iess'=>'Préstamos IESS','ret_renta'=>'Ret. renta','otros_descuentos'=>'Otros'] as $k=>$lbl)
                    @if((float)$r->$k > 0)<div style="display:flex;justify-content:space-between;padding:.2rem 0;font-size:.88rem"><span style="color:#475569">{{ $lbl }}</span><span>(${{ number_format($r->$k,2) }})</span></div>@endif
                @endforeach
                <div style="display:flex;justify-content:space-between;padding:.4rem 0;border-top:1px solid #f1f5f9;font-weight:700"><span>Total descuentos</span><span>(${{ number_format($r->total_descuentos,2) }})</span></div>
            </div>
        </div>

        <div style="padding:0 1.25rem 1rem">
            <div style="display:flex;justify-content:space-between;padding:.7rem 1rem;background:#f0fdf4;border-radius:10px;font-weight:800;font-size:1.1rem;color:#15803d">
                <span>Líquido a recibir</span><span>${{ number_format($r->liquido,2) }}</span>
            </div>
        </div>

        @if($r->relacion !== 'honorarios')
        <div style="padding:0 1.25rem 1.25rem">
            <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:#8a949e;font-weight:600;margin-bottom:.5rem">Provisiones patronales (costo empresa, no se descuenta al empleado)</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.3rem .5rem;font-size:.85rem">
                <div style="display:flex;justify-content:space-between"><span style="color:#475569">Aporte patronal (11,15%)</span><span>${{ number_format($r->aporte_patronal,2) }}</span></div>
                <div style="display:flex;justify-content:space-between"><span style="color:#475569">Décimo tercero</span><span>${{ number_format($r->decimo_tercero,2) }}</span></div>
                <div style="display:flex;justify-content:space-between"><span style="color:#475569">Décimo cuarto</span><span>${{ number_format($r->decimo_cuarto,2) }}</span></div>
                <div style="display:flex;justify-content:space-between"><span style="color:#475569">Vacaciones</span><span>${{ number_format($r->vacaciones,2) }}</span></div>
                @if((float)$r->fondos_reserva > 0)<div style="display:flex;justify-content:space-between"><span style="color:#475569">Fondos de reserva (8,33%)</span><span>${{ number_format($r->fondos_reserva,2) }}</span></div>@endif
            </div>
            <div style="display:flex;justify-content:space-between;padding:.5rem 0;margin-top:.4rem;border-top:2px solid #e2e8f0;font-weight:700">
                <span>Costo total para la empresa</span><span>${{ number_format($r->costo_empresa,2) }}</span>
            </div>
        </div>
        @endif

        @if($r->estado === 'pagado')
        {{-- respaldo-pago --}}
        <div style="padding:0 1.25rem 1.25rem">
            <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:#8a949e;font-weight:600;margin-bottom:.5rem">Respaldo del pago</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.3rem .5rem;font-size:.85rem">
                <div style="display:flex;justify-content:space-between"><span style="color:#475569">Fecha de pago</span><span>{{ $r->fecha_pago ? \Illuminate\Support\Carbon::parse($r->fecha_pago)->format('d/m/Y') : '—' }}</span></div>
                <div style="display:flex;justify-content:space-between"><span style="color:#475569">Método</span><span>{{ ucfirst($r->metodo_pago ?: '—') }}</span></div>
                @if($r->nro_comprobante_pago)<div style="display:flex;justify-content:space-between"><span style="color:#475569">N° comprobante</span><span>{{ $r->nro_comprobante_pago }}</span></div>@endif
                @if($r->banco_pago)<div style="display:flex;justify-content:space-between"><span style="color:#475569">Banco</span><span>{{ $r->banco_pago }}</span></div>@endif
            </div>
            @if($r->nota_pago)<p style="margin:.5rem 0 0;font-size:.85rem;color:#475569"><strong>Nota:</strong> {{ $r->nota_pago }}</p>@endif
            @if($r->adjunto_pago)<p style="margin:.5rem 0 0"><a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($r->adjunto_pago) }}" target="_blank" style="color:var(--brand,#2563eb);font-size:.85rem;font-weight:600">Ver comprobante adjunto</a></p>@endif
        </div>
        @endif
    </div>
</x-filament-panels::page>
