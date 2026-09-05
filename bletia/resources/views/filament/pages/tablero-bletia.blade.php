<x-filament-panels::page>
    @php($acc = $this->accesos())
    @php($lst = $this->listas())
    @php($kb = $this->kanban())
    @php($g = $this->gantt())

    <style>
        .blt-wrap{font-family:'Inter',ui-sans-serif,system-ui,sans-serif}
        .blt-quick{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin-bottom:14px}
        .blt-qcard{display:flex;align-items:center;gap:12px;background:#fff;border:1px solid #eef0f4;border-radius:14px;padding:15px 16px;box-shadow:0 1px 2px rgba(16,24,40,.04);transition:.15s;text-decoration:none}
        .blt-qcard:hover{box-shadow:0 6px 16px rgba(16,24,40,.10);transform:translateY(-1px)}
        .blt-qico{width:40px;height:40px;border-radius:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
        .blt-qt{font-weight:650;font-size:14px;color:#1d2433;line-height:1.2}
        .blt-chips{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:24px}
        .blt-chip{display:inline-flex;align-items:center;gap:7px;background:#fff;border:1px solid #eef0f4;border-radius:10px;padding:8px 14px;color:#475067;font-size:13px;font-weight:600;text-decoration:none;transition:.15s}
        .blt-chip:hover{border-color:#d6dae2;color:#1d2433}
        .blt-sec{font-weight:700;font-size:13px;letter-spacing:.02em;text-transform:uppercase;color:#8a93a6;margin:0 0 14px}
        .blt-board{display:grid;grid-template-columns:repeat({{ count($kb) }},minmax(210px,1fr));gap:14px;overflow-x:auto;padding-bottom:6px;margin-bottom:30px}
        .blt-col{background:#f7f8fa;border-radius:14px;padding:6px 6px 10px;min-width:210px}
        .blt-colhead{display:flex;align-items:center;justify-content:space-between;padding:12px 12px 10px}
        .blt-coltitle{display:flex;align-items:center;gap:8px;font-weight:680;font-size:13px;color:#1d2433}
        .blt-dot{width:9px;height:9px;border-radius:3px}
        .blt-count{background:#fff;color:#6b7385;border:1px solid #e7e9ef;border-radius:8px;padding:1px 9px;font-size:12px;font-weight:700}
        .blt-card{display:block;background:#fff;border:1px solid #edeff3;border-radius:11px;padding:12px;margin:0 6px 9px;text-decoration:none;box-shadow:0 1px 2px rgba(16,24,40,.04);transition:.15s}
        .blt-card:hover{box-shadow:0 5px 14px rgba(16,24,40,.10);transform:translateY(-1px)}
        .blt-card.atr{border-left:3px solid #f04438}
        .blt-folio{font-weight:700;font-size:12.5px;color:#1d2433;margin-bottom:5px}
        .blt-cli{display:flex;align-items:center;gap:7px;margin-bottom:9px}
        .blt-av{width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;flex-shrink:0}
        .blt-cliname{font-size:12px;color:#6b7385}
        .blt-pill{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:650;padding:3px 9px;border-radius:7px}
        .blt-empty{font-size:12px;color:#b6bdc9;text-align:center;padding:14px 0}
        .blt-gantt{background:#fff;border:1px solid #eef0f4;border-radius:16px;padding:18px;box-shadow:0 1px 2px rgba(16,24,40,.04)}
        .blt-grow{display:flex;align-items:center;gap:10px;text-decoration:none;padding:7px 0;border-bottom:1px solid #f2f3f6}
        .blt-glabel{width:230px;flex-shrink:0}
        .blt-track{position:relative;flex:1;height:24px;background:#f2f3f6;border-radius:7px}
        .blt-bar{position:absolute;top:0;height:24px;border-radius:7px;display:flex;align-items:center;justify-content:flex-end;padding-right:8px;color:#fff;font-size:10.5px;font-weight:700}
    </style>

    <div class="blt-wrap">
        {{-- Accesos rápidos --}}
        @if($acc)
        <div class="blt-quick">
            @foreach($acc as $b)
                <a href="{{ $b['u'] }}" class="blt-qcard">
                    <span class="blt-qico" style="background:{{ $b['c'] }}1a">
                        <x-filament::icon :icon="$b['i']" style="width:21px;height:21px;color:{{ $b['c'] }}" />
                    </span>
                    <span class="blt-qt">{{ $b['t'] }}</span>
                </a>
            @endforeach
        </div>
        @endif

        @if($lst)
        <div class="blt-chips">
            @foreach($lst as $b)
                <a href="{{ $b['u'] }}" class="blt-chip"><x-filament::icon :icon="$b['i']" style="width:17px;height:17px" /> {{ $b['t'] }}</a>
            @endforeach
        </div>
        @endif

        {{-- Kanban --}}
        <div class="blt-sec">Flujo por etapa</div>
        <div class="blt-board">
            @foreach($kb as $col)
                <div class="blt-col">
                    <div class="blt-colhead">
                        <span class="blt-coltitle"><span class="blt-dot" style="background:{{ $col['color'] }}"></span>{{ $col['titulo'] }}</span>
                        <span class="blt-count">{{ $col['n'] }}</span>
                    </div>
                    @forelse($col['cards'] as $c)
                        <a href="{{ $c['url'] }}" class="blt-card {{ $c['atrasado'] ? 'atr' : '' }}">
                            <div class="blt-folio">{{ $c['folio'] }}</div>
                            <div class="blt-cli">
                                <span class="blt-av" style="background:{{ $col['color'] }}">{{ strtoupper(mb_substr($c['cliente'],0,1)) }}</span>
                                <span class="blt-cliname">{{ \Illuminate\Support\Str::limit($c['cliente'],18) }}</span>
                            </div>
                            <span class="blt-pill" style="background:{{ $c['atrasado'] ? '#fee4e2' : $col['color'].'1a' }};color:{{ $c['atrasado'] ? '#d92d20' : $col['color'] }}">
                                <x-filament::icon icon="heroicon-o-calendar" style="width:13px;height:13px" />
                                {{ $c['fin'] }}{{ $c['atrasado'] ? ' · atrasado' : '' }}
                            </span>
                        </a>
                    @empty
                        <div class="blt-empty">Sin pedidos</div>
                    @endforelse
                </div>
            @endforeach
        </div>

        {{-- Entregas: resumen + tabla --}}
        <div class="blt-sec">Entregas en proceso</div>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:18px;">
            <div style="background:#f8f9fb;border-radius:10px;padding:14px 16px;">
                <div style="font-size:12.5px;color:#8a929c;">Pedidos en proceso</div>
                <div style="font-size:26px;font-weight:700;color:#161921;">{{ $g['totalPed'] }}</div>
            </div>
            <div style="background:#f0f9f4;border-radius:10px;padding:14px 16px;">
                <div style="font-size:12.5px;color:#8a929c;">En tiempo</div>
                <div style="font-size:26px;font-weight:700;color:#1f8b4c;">{{ $g['enTiempoN'] }}</div>
            </div>
            <div style="background:{{ $g['atrasadosN'] > 0 ? '#fdf0ef' : '#f8f9fb' }};border-radius:10px;padding:14px 16px;">
                <div style="font-size:12.5px;color:#8a929c;">Atrasados</div>
                <div style="font-size:26px;font-weight:700;color:{{ $g['atrasadosN'] > 0 ? '#c0392b' : '#161921' }};">{{ $g['atrasadosN'] }}</div>
            </div>
        </div>

        <div class="blt-gantt" style="padding:0;overflow:hidden;">
            @if(count($g['filas']))
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="background:#f8f9fb;">
                        <th style="text-align:left;padding:10px 14px;color:#8a929c;font-weight:600;font-size:11.5px;text-transform:uppercase;letter-spacing:.4px;">Pedido</th>
                        <th style="text-align:left;padding:10px 14px;color:#8a929c;font-weight:600;font-size:11.5px;text-transform:uppercase;letter-spacing:.4px;">Cliente</th>
                        <th style="text-align:left;padding:10px 14px;color:#8a929c;font-weight:600;font-size:11.5px;text-transform:uppercase;letter-spacing:.4px;">Estado</th>
                        <th style="text-align:left;padding:10px 14px;color:#8a929c;font-weight:600;font-size:11.5px;text-transform:uppercase;letter-spacing:.4px;">Entrega</th>
                        <th style="text-align:right;padding:10px 14px;color:#8a929c;font-weight:600;font-size:11.5px;text-transform:uppercase;letter-spacing:.4px;">Situación</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($g['filas'] as $f)
                        <tr style="border-top:1px solid #edeff2;cursor:pointer;" data-url="{{ $f['url'] }}" onclick="window.location.href=this.dataset.url">
                            <td style="padding:10px 14px;font-weight:700;color:#161921;">{{ $f['folio'] }}</td>
                            <td style="padding:10px 14px;color:#2b3038;">{{ \Illuminate\Support\Str::limit($f['cliente'],22) }}</td>
                            <td style="padding:10px 14px;color:#5b6470;">{{ $f['estado'] }}</td>
                            <td style="padding:10px 14px;color:#2b3038;">{{ $f['fin'] }}</td>
                            <td style="padding:10px 14px;text-align:right;">
                                @if($f['atrasado'])
                                    <span style="background:#fdf0ef;color:#c0392b;padding:4px 12px;border-radius:999px;font-size:12px;font-weight:600;">Atrasado {{ abs($f['dias']) }} día(s)</span>
                                @else
                                    <span style="background:#f0f9f4;color:#1f8b4c;padding:4px 12px;border-radius:999px;font-size:12px;font-weight:600;">En tiempo · faltan {{ $f['dias'] }} día(s)</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @else
                <div class="blt-empty">No hay pedidos en proceso para tu rol.</div>
            @endif
        </div>
    </div>

    @php($pf = $this->panelFinanciero())
    @if($pf)
    <div class="blt-sec" style="margin-top:24px;">Panel financiero</div>
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px; margin-bottom:24px;">
        <div style="background:#fff; border:1px solid #edeff2; border-radius:14px; padding:16px;">
            <div style="color:#8a929c; font-size:12px; text-transform:uppercase; letter-spacing:.5px;">Cuentas por cobrar</div>
            <div style="font-size:24px; font-weight:bold; color:{{ $pf['cuentas_por_cobrar'] > 0 ? '#c0392b' : '#1f8b4c' }};">$ {{ number_format($pf['cuentas_por_cobrar'], 2) }}</div>
            <div style="color:#8a929c; font-size:12px;">{{ $pf['pedidos_con_deuda'] }} pedido(s) con saldo</div>
        </div>
        <div style="background:#fff; border:1px solid #edeff2; border-radius:14px; padding:16px;">
            <div style="color:#8a929c; font-size:12px; text-transform:uppercase; letter-spacing:.5px;">Utilidad del mes {{ $pf['utilidad_completa'] ? '' : '(parcial)' }}</div>
            <div style="font-size:24px; font-weight:bold; color:#161921;">$ {{ number_format($pf['utilidad_mes'], 2) }}</div>
            @if(!$pf['utilidad_completa'])
                <div style="color:#b8860b; font-size:12px;">Faltan costos en algunos productos</div>
            @else
                <div style="color:#8a929c; font-size:12px;">Sobre $ {{ number_format($pf['venta_total_mes'], 2) }} vendidos</div>
            @endif
        </div>
        <div style="background:#fff; border:1px solid #edeff2; border-radius:14px; padding:16px;">
            <div style="color:#8a929c; font-size:12px; text-transform:uppercase; letter-spacing:.5px;">Ventas vs mes anterior</div>
            @if(!is_null($pf['variacion']))
                <div style="font-size:24px; font-weight:bold; color:{{ $pf['variacion'] >= 0 ? '#1f8b4c' : '#c0392b' }};">{{ $pf['variacion'] >= 0 ? '+' : '' }}{{ $pf['variacion'] }}%</div>
                <div style="color:#8a929c; font-size:12px;">Mes anterior: $ {{ number_format($pf['ventas_mes_anterior'], 2) }}</div>
            @else
                <div style="font-size:18px; color:#8a929c;">Sin datos del mes anterior</div>
            @endif
        </div>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:14px; margin-bottom:24px;">
        <div style="background:#fff; border:1px solid #edeff2; border-radius:14px; padding:16px;">
            <div style="color:#8a929c; font-size:12px; text-transform:uppercase; letter-spacing:.5px; margin-bottom:10px;">Top 5 productos del mes</div>
            @forelse($pf['top_productos'] as $tp)
                <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid #f4f6f8; font-size:13px;">
                    <span>{{ $tp->nombre }} <span style="color:#8a929c;">×{{ (int) $tp->total_cant }}</span></span>
                    <strong>$ {{ number_format((float) $tp->total_venta, 2) }}</strong>
                </div>
            @empty
                <div style="color:#8a929c; font-size:13px;">Sin ventas este mes.</div>
            @endforelse
        </div>
        <div style="background:#fff; border:1px solid #edeff2; border-radius:14px; padding:16px;">
            <div style="color:#8a929c; font-size:12px; text-transform:uppercase; letter-spacing:.5px; margin-bottom:10px;">Cheques próximos (7 días)</div>
            @forelse($pf['cheques_proximos'] as $ch)
                <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid #f4f6f8; font-size:13px;">
                    <span>N° {{ $ch->cheque_numero ?: '—' }} · {{ $ch->cheque_banco ?: '—' }} <span style="color:#8a929c;">{{ \Illuminate\Support\Carbon::parse($ch->cheque_fecha_cobro)->format('d/m') }}</span></span>
                    <strong>$ {{ number_format((float) $ch->monto, 2) }}</strong>
                </div>
            @empty
                <div style="color:#8a929c; font-size:13px;">Sin cheques próximos.</div>
            @endforelse
            <a href="/dash/cheques-por-cobrar" style="display:inline-block; margin-top:10px; color:#0499FC; font-size:12px; text-decoration:none;">Ver todos →</a>
        </div>
    </div>
    @endif
</x-filament-panels::page>
