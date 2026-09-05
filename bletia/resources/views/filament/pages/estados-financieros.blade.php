<x-filament-panels::page>
    <form wire:submit="calcular">
        {{ $this->form }}
        <div style="margin-top:1rem">
            <x-filament::button type="submit" color="gray" icon="heroicon-o-arrow-path">Actualizar</x-filament::button>
        </div>
    </form>

    @php($r = $resultados)
    @php($b = $balance)

    <div style="margin-top:1.5rem;display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:1.25rem">

        {{-- ESTADO DE RESULTADOS --}}
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden">
            <div style="background:#161921;color:#fff;padding:.85rem 1.15rem;font-weight:700">Estado de resultados</div>
            <div style="padding:1rem 1.15rem">
                @if(!empty($r))
                    <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;color:#8a949e;font-weight:600;margin-bottom:.4rem">Ingresos</div>
                    @foreach($r['ingresos'] as $c)
                        <div style="display:flex;justify-content:space-between;padding:.2rem 0;font-size:.88rem"><span style="color:#475569">{{ $c->nombre }}</span><span>${{ number_format($c->saldo, 2) }}</span></div>
                    @endforeach
                    <div style="display:flex;justify-content:space-between;padding:.35rem 0;border-top:1px solid #f1f5f9;font-weight:600"><span>Total ingresos</span><span>${{ number_format($r['total_ingresos'], 2) }}</span></div>

                    @if($r['total_costos'] != 0)
                        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;color:#8a949e;font-weight:600;margin:.7rem 0 .4rem">Costo de ventas</div>
                        @foreach($r['costos'] as $c)
                            <div style="display:flex;justify-content:space-between;padding:.2rem 0;font-size:.88rem"><span style="color:#475569">{{ $c->nombre }}</span><span>(${{ number_format($c->saldo, 2) }})</span></div>
                        @endforeach
                        <div style="display:flex;justify-content:space-between;padding:.35rem 0;border-top:1px solid #f1f5f9;font-weight:600"><span>Utilidad bruta</span><span>${{ number_format($r['utilidad_bruta'], 2) }}</span></div>
                    @endif

                    <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;color:#8a949e;font-weight:600;margin:.7rem 0 .4rem">Gastos</div>
                    @foreach($r['gastos'] as $c)
                        <div style="display:flex;justify-content:space-between;padding:.2rem 0;font-size:.88rem"><span style="color:#475569">{{ $c->nombre }}</span><span>(${{ number_format($c->saldo, 2) }})</span></div>
                    @endforeach
                    <div style="display:flex;justify-content:space-between;padding:.35rem 0;border-top:1px solid #f1f5f9;font-weight:600"><span>Total gastos</span><span>(${{ number_format($r['total_gastos'], 2) }})</span></div>

                    <div style="display:flex;justify-content:space-between;padding:.6rem 0;margin-top:.4rem;border-top:2px solid #e2e8f0;font-weight:800;font-size:1.05rem;color:{{ $r['utilidad_neta'] >= 0 ? '#15803d' : '#b91c1c' }}">
                        <span>Utilidad neta</span><span>${{ number_format($r['utilidad_neta'], 2) }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- BALANCE GENERAL --}}
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden">
            <div style="background:#161921;color:#fff;padding:.85rem 1.15rem;font-weight:700">Balance general · corte {{ $b['hasta'] ?? '' }}</div>
            <div style="padding:1rem 1.15rem">
                @if(!empty($b))
                    <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;color:#8a949e;font-weight:600;margin-bottom:.4rem">Activo</div>
                    @foreach($b['activo'] as $c)
                        <div style="display:flex;justify-content:space-between;padding:.2rem 0;font-size:.88rem"><span style="color:#475569">{{ $c->nombre }}</span><span>${{ number_format($c->saldo, 2) }}</span></div>
                    @endforeach
                    <div style="display:flex;justify-content:space-between;padding:.35rem 0;border-top:1px solid #f1f5f9;font-weight:700"><span>Total activo</span><span>${{ number_format($b['total_activo'], 2) }}</span></div>

                    <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;color:#8a949e;font-weight:600;margin:.7rem 0 .4rem">Pasivo</div>
                    @foreach($b['pasivo'] as $c)
                        <div style="display:flex;justify-content:space-between;padding:.2rem 0;font-size:.88rem"><span style="color:#475569">{{ $c->nombre }}</span><span>${{ number_format($c->saldo, 2) }}</span></div>
                    @endforeach
                    <div style="display:flex;justify-content:space-between;padding:.35rem 0;border-top:1px solid #f1f5f9;font-weight:600"><span>Total pasivo</span><span>${{ number_format($b['total_pasivo'], 2) }}</span></div>

                    <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;color:#8a949e;font-weight:600;margin:.7rem 0 .4rem">Patrimonio</div>
                    @foreach($b['patrimonio'] as $c)
                        <div style="display:flex;justify-content:space-between;padding:.2rem 0;font-size:.88rem"><span style="color:#475569">{{ $c->nombre }}</span><span>${{ number_format($c->saldo, 2) }}</span></div>
                    @endforeach
                    <div style="display:flex;justify-content:space-between;padding:.2rem 0;font-size:.88rem"><span style="color:#475569">Utilidad del ejercicio</span><span>${{ number_format($b['utilidad_ejercicio'], 2) }}</span></div>
                    <div style="display:flex;justify-content:space-between;padding:.35rem 0;border-top:1px solid #f1f5f9;font-weight:600"><span>Total patrimonio</span><span>${{ number_format($b['total_patrimonio'], 2) }}</span></div>

                    <div style="display:flex;justify-content:space-between;padding:.6rem 0;margin-top:.4rem;border-top:2px solid #e2e8f0;font-weight:800"><span>Pasivo + Patrimonio</span><span>${{ number_format($b['total_pasivo_patrimonio'], 2) }}</span></div>

                    <div style="margin-top:.6rem;padding:.5rem .75rem;border-radius:8px;font-size:.82rem;font-weight:600;text-align:center;
                                background:{{ $b['cuadra'] ? '#f0fdf4' : '#fef2f2' }};color:{{ $b['cuadra'] ? '#15803d' : '#b91c1c' }}">
                        {{ $b['cuadra'] ? '✓ Activo = Pasivo + Patrimonio' : '✗ Descuadre de $' . number_format(abs($b['descuadre']), 2) }}
                    </div>
                @endif
            </div>
        </div>

    </div>

    <p style="margin-top:1rem;color:#6b7280;font-size:.82rem">
        Derivado de los asientos registrados. El estado de resultados usa el rango [desde, hasta];
        el balance general es a la fecha de corte (hasta). Si el balance no cuadra, revisa que existan
        asientos de apertura (capital inicial) o partidas faltantes.
    </p>
</x-filament-panels::page>
