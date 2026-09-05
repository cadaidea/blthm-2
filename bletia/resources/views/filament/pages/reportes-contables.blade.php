<x-filament-panels::page>
    <form wire:submit="calcular">
        {{ $this->form }}
        <div style="margin-top:1rem">
            <x-filament::button type="submit" color="gray" icon="heroicon-o-arrow-path">Actualizar</x-filament::button>
        </div>
    </form>

    @php($cuadra = round($totales['debe'] - $totales['haber'], 2) === 0.0)

    <div style="margin-top:1.25rem;padding:.75rem 1.15rem;border-radius:10px;font-weight:600;
                background:{{ $cuadra ? '#f0fdf4' : '#fef2f2' }};color:{{ $cuadra ? '#15803d' : '#b91c1c' }}">
        {{ $cuadra ? '✓ El balance cuadra' : '✗ Descuadre de $' . number_format(abs($totales['debe'] - $totales['haber']), 2) }}
        · Debe ${{ number_format($totales['debe'], 2) }} · Haber ${{ number_format($totales['haber'], 2) }}
    </div>

    <div style="margin-top:1rem;background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:auto">
        <table style="width:100%;border-collapse:collapse;font-size:.88rem">
            <thead>
                <tr style="background:#f8fafc;text-align:left">
                    <th style="padding:.65rem 1rem;color:#475569;font-weight:600">Código</th>
                    <th style="padding:.65rem 1rem;color:#475569;font-weight:600">Cuenta</th>
                    <th style="padding:.65rem 1rem;color:#475569;font-weight:600;text-align:right">Debe</th>
                    <th style="padding:.65rem 1rem;color:#475569;font-weight:600;text-align:right">Haber</th>
                    <th style="padding:.65rem 1rem;color:#475569;font-weight:600;text-align:right">Saldo</th>
                </tr>
            </thead>
            <tbody>
                @forelse($balance as $b)
                    <tr style="border-top:1px solid #f1f5f9">
                        <td style="padding:.5rem 1rem;color:#64748b;font-variant-numeric:tabular-nums">{{ $b['codigo'] }}</td>
                        <td style="padding:.5rem 1rem">{{ $b['nombre'] }}</td>
                        <td style="padding:.5rem 1rem;text-align:right">{{ $b['debe'] > 0 ? '$' . number_format($b['debe'], 2) : '' }}</td>
                        <td style="padding:.5rem 1rem;text-align:right">{{ $b['haber'] > 0 ? '$' . number_format($b['haber'], 2) : '' }}</td>
                        <td style="padding:.5rem 1rem;text-align:right;font-weight:600;color:{{ $b['saldo'] < 0 ? '#b91c1c' : '#161921' }}">${{ number_format($b['saldo'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="padding:2rem;text-align:center;color:#8a949e">Sin movimientos en el periodo. Cuando registres asientos (o se automaticen), aparecerán aquí.</td></tr>
                @endforelse
            </tbody>
            @if(count($balance))
                <tfoot>
                    <tr style="border-top:2px solid #e2e8f0;font-weight:700;background:#fafafa">
                        <td colspan="2" style="padding:.65rem 1rem;text-align:right">Totales</td>
                        <td style="padding:.65rem 1rem;text-align:right">${{ number_format($totales['debe'], 2) }}</td>
                        <td style="padding:.65rem 1rem;text-align:right">${{ number_format($totales['haber'], 2) }}</td>
                        <td style="padding:.65rem 1rem"></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    <p style="margin-top:1rem;color:#6b7280;font-size:.82rem">
        Balance de comprobación del periodo. En la fase siguiente (4B) las ventas, compras, cobros y pagos
        generarán sus asientos automáticamente; este balance se llenará solo.
    </p>
</x-filament-panels::page>
