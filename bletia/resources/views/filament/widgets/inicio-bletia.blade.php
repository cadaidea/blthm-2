<div class="bletia-inicio">
    @if(($porAprobar ?? 0) > 0)
        <a href="/dash/pedido-especials" class="bletia-alarma">
            <x-filament::icon icon="heroicon-o-bell-alert"/>
            <span><strong>{{ $porAprobar }}</strong> pedido(s) requieren tu aprobación</span>
        </a>
    @endif

    <div class="bletia-metricas">
        @foreach($metricas as $mt)
            <div class="bletia-metrica" style="--c: {{ $mt['color'] }}">
                <div class="bletia-metrica-ic"><x-filament::icon :icon="'heroicon-o-'.$mt['icon']"/></div>
                <div class="bletia-metrica-val">{{ $mt['valor'] }}</div>
                <div class="bletia-metrica-lbl">{{ $mt['label'] }}</div>
            </div>
        @endforeach
    </div>

    <h2 class="bletia-h2">Accesos rápidos</h2>
    <div class="bletia-grid">
        @foreach($accesos as $a)
            <a href="{{ $a['url'] }}" class="bletia-card-acc">
                <span class="bletia-ic" style="background: {{ $a['color'] }}1a; color: {{ $a['color'] }}">
                    <x-filament::icon :icon="'heroicon-o-'.$a['icon']"/>
                </span>
                <span class="bletia-card-label">{{ $a['label'] }}</span>
            </a>
        @endforeach
    </div>

    @if(!empty($gerencia))
    <h2 class="bletia-h2">Panel financiero</h2>
    <div class="bletia-gerencia" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px; margin-bottom:24px;">
        <div style="background:#fff; border:1px solid #edeff2; border-radius:14px; padding:16px;">
            <div style="color:#8a929c; font-size:12px; text-transform:uppercase; letter-spacing:.5px;">Cuentas por cobrar</div>
            <div style="font-size:24px; font-weight:bold; color:{{ $gerencia['cuentas_por_cobrar'] > 0 ? '#c0392b' : '#1f8b4c' }};">$ {{ number_format($gerencia['cuentas_por_cobrar'], 2) }}</div>
            <div style="color:#8a929c; font-size:12px;">{{ $gerencia['pedidos_con_deuda'] }} pedido(s) con saldo</div>
        </div>
        <div style="background:#fff; border:1px solid #edeff2; border-radius:14px; padding:16px;">
            <div style="color:#8a929c; font-size:12px; text-transform:uppercase; letter-spacing:.5px;">Utilidad del mes {{ $gerencia['utilidad_completa'] ? '' : '(parcial)' }}</div>
            <div style="font-size:24px; font-weight:bold; color:#161921;">$ {{ number_format($gerencia['utilidad_mes'], 2) }}</div>
            @if(!$gerencia['utilidad_completa'])
                <div style="color:#b8860b; font-size:12px;">Faltan costos en algunos productos</div>
            @else
                <div style="color:#8a929c; font-size:12px;">Sobre $ {{ number_format($gerencia['venta_total_mes'], 2) }} vendidos</div>
            @endif
        </div>
        <div style="background:#fff; border:1px solid #edeff2; border-radius:14px; padding:16px;">
            <div style="color:#8a929c; font-size:12px; text-transform:uppercase; letter-spacing:.5px;">Ventas vs mes anterior</div>
            @if(!is_null($gerencia['variacion']))
                <div style="font-size:24px; font-weight:bold; color:{{ $gerencia['variacion'] >= 0 ? '#1f8b4c' : '#c0392b' }};">{{ $gerencia['variacion'] >= 0 ? '+' : '' }}{{ $gerencia['variacion'] }}%</div>
                <div style="color:#8a929c; font-size:12px;">Mes anterior: $ {{ number_format($gerencia['ventas_mes_anterior'], 2) }}</div>
            @else
                <div style="font-size:18px; color:#8a929c;">Sin datos del mes anterior</div>
            @endif
        </div>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:14px; margin-bottom:24px;">
        <div style="background:#fff; border:1px solid #edeff2; border-radius:14px; padding:16px;">
            <div style="color:#8a929c; font-size:12px; text-transform:uppercase; letter-spacing:.5px; margin-bottom:10px;">Top 5 productos del mes</div>
            @forelse($gerencia['top_productos'] as $tp)
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
            @forelse($gerencia['cheques_proximos'] as $ch)
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
    <h2 class="bletia-h2">Pedidos recientes</h2>
    <div class="bletia-list">
        @forelse($pedidos as $p)
            <a href="/dash/pedido-especials/{{ $p['id'] }}" class="bletia-row">
                <div class="bletia-row-main">
                    <span class="bletia-folio">{{ $p['folio'] }}</span>
                    <span class="bletia-forma">{{ ucfirst($p['forma']) }}</span>
                </div>
                <div class="bletia-row-meta">
                    <span class="bletia-estado bletia-e-{{ $p['estado'] }}">{{ str_replace('_',' ',$p['estado']) }}</span>
                    @if(!is_null($p['dias']))
                        <span class="bletia-dias {{ $p['dias'] < 0 ? 'late' : '' }}">{{ $p['dias'] < 0 ? abs($p['dias']).'d tarde' : 'en '.$p['dias'].'d' }}</span>
                    @endif
                    <span class="bletia-total">${{ $p['total'] }}</span>
                </div>
            </a>
        @empty
            <div class="bletia-empty">Aún no hay pedidos.</div>
        @endforelse
    </div>
</div>
