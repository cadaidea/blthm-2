<x-filament-panels::page>
    @php($items = $this->getItems())
    <div style="background:#fff; border:1px solid #edeff2; border-radius:14px; overflow:hidden;">
        @if(count($items))
        <table style="width:100%; border-collapse:collapse; font-size:13px;">
            <thead>
                <tr style="background:#f8f9fb;">
                    <th style="text-align:left; padding:10px 14px; color:#8a929c; font-weight:600; font-size:11.5px; text-transform:uppercase;">Origen</th>
                    <th style="text-align:left; padding:10px 14px; color:#8a929c; font-weight:600; font-size:11.5px; text-transform:uppercase;">Folio</th>
                    <th style="text-align:left; padding:10px 14px; color:#8a929c; font-weight:600; font-size:11.5px; text-transform:uppercase;">Para</th>
                    <th style="text-align:left; padding:10px 14px; color:#8a929c; font-weight:600; font-size:11.5px; text-transform:uppercase;">Productos</th>
                    <th style="text-align:center; padding:10px 14px; color:#8a929c; font-weight:600; font-size:11.5px; text-transform:uppercase;">Días fab.</th>
                    <th style="text-align:right; padding:10px 14px; color:#8a929c; font-weight:600; font-size:11.5px; text-transform:uppercase;">Entrega</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $it)
                <tr style="border-top:1px solid #edeff2; cursor:pointer;" onclick="window.location.href='{{ $it['url'] }}'">
                    <td style="padding:10px 14px;">
                        <span style="background:{{ $it['origen'] === 'cliente' ? '#eef6ff' : '#fdf3e3' }}; color:{{ $it['origen'] === 'cliente' ? '#0499FC' : '#b8860b' }}; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:bold;">
                            {{ $it['origen'] === 'cliente' ? 'Cliente' : 'Abastecimiento' }}
                        </span>
                    </td>
                    <td style="padding:10px 14px; font-weight:700; color:#161921;">{{ $it['folio'] }}</td>
                    <td style="padding:10px 14px; color:#2b3038;">{{ $it['titulo'] }}</td>
                    <td style="padding:10px 14px; color:#5b6470;">{{ \Illuminate\Support\Str::limit($it['detalle'], 40) }}</td>
                    <td style="padding:10px 14px; text-align:center; color:#2b3038;">{{ $it['dias_fab'] ?: '—' }}</td>
                    <td style="padding:10px 14px; text-align:right;">
                        @if($it['fecha_limite'])
                            <span style="color:{{ $it['atrasado'] ? '#c0392b' : '#2b3038' }}; font-weight:{{ $it['atrasado'] ? 'bold' : 'normal' }};">
                                {{ $it['fecha_limite']->format('d/m/Y') }}{{ $it['atrasado'] ? ' (atrasado)' : '' }}
                            </span>
                        @else
                            <span style="color:#8a929c;">Sin fecha límite</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
            <div style="padding:40px; text-align:center; color:#8a929c;">No hay nada en producción ahora mismo.</div>
        @endif
    </div>
    <p style="font-size:12px; color:#8a929c; margin-top:10px;">Ordenado por urgencia: atrasados primero, luego por fecha de entrega más próxima. "Días fab." es el tiempo estimado del mueble más lento del pedido/orden.</p>
</x-filament-panels::page>
