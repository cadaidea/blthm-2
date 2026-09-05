<x-filament-panels::page>
    {{ $this->form }}

    <div style="margin-top:1.25rem;background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:auto">
        <table style="width:100%;border-collapse:collapse;font-size:.9rem">
            <thead>
                <tr style="background:#f8fafc;text-align:left">
                    <th style="padding:.7rem 1rem;color:#475569;font-weight:600">Empleado</th>
                    <th style="padding:.7rem 1rem;color:#475569;font-weight:600;text-align:right">Días ganados</th>
                    <th style="padding:.7rem 1rem;color:#475569;font-weight:600;text-align:right">Días tomados</th>
                    <th style="padding:.7rem 1rem;color:#475569;font-weight:600;text-align:right">Días pendientes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($filas as $f)
                    <tr style="border-top:1px solid #f1f5f9">
                        <td style="padding:.6rem 1rem;font-weight:600">{{ $f['empleado'] }}</td>
                        <td style="padding:.6rem 1rem;text-align:right">{{ number_format($f['ganados'], 2) }}</td>
                        <td style="padding:.6rem 1rem;text-align:right">{{ number_format($f['tomados'], 2) }}</td>
                        <td style="padding:.6rem 1rem;text-align:right;font-weight:700;color:{{ $f['pendientes'] < 0 ? '#b91c1c' : '#161921' }}">{{ number_format($f['pendientes'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="padding:2rem;text-align:center;color:#8a949e">Sin empleados en dependencia.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p style="margin-top:1rem;color:#6b7280;font-size:.82rem">
        Días calendario, proporcional a los meses trabajados (Art. 69 Código de Trabajo).
        Este control es independiente del valor en dólares que ya se provisiona en cada rol de pago.
        Si un empleado sale de la empresa con días pendientes, la Liquidación los toma del valor
        acumulado en dólares; este tablero es para llevar el control en días y su documentación.
    </p>
</x-filament-panels::page>
