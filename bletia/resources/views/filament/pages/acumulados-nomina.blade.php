<x-filament-panels::page>
    <p style="color:#6b7280;font-size:.9rem;margin-bottom:1rem">
        Lo que tienes provisionado por empleado (décimos y fondos en modo acumulado, y vacaciones no gozadas),
        menos lo ya pagado. Usa "Pagar beneficio" cuando llegue el mes legal para descargar la provisión.
    </p>

    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:auto">
        <table style="width:100%;border-collapse:collapse;font-size:.9rem">
            <thead>
                <tr style="background:#f8fafc;text-align:left">
                    <th style="padding:.7rem 1rem;color:#475569;font-weight:600">Empleado</th>
                    <th style="padding:.7rem 1rem;color:#475569;font-weight:600;text-align:right">Décimo 13º</th>
                    <th style="padding:.7rem 1rem;color:#475569;font-weight:600;text-align:right">Décimo 14º</th>
                    <th style="padding:.7rem 1rem;color:#475569;font-weight:600;text-align:right">Vacaciones</th>
                    <th style="padding:.7rem 1rem;color:#475569;font-weight:600;text-align:right">Fondos reserva</th>
                    <th style="padding:.7rem 1rem;color:#475569;font-weight:600;text-align:right">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($filas as $f)
                    <tr style="border-top:1px solid #f1f5f9">
                        <td style="padding:.6rem 1rem;font-weight:600">{{ $f['empleado'] }}</td>
                        <td style="padding:.6rem 1rem;text-align:right">${{ number_format($f['decimo_tercero'], 2) }}</td>
                        <td style="padding:.6rem 1rem;text-align:right">${{ number_format($f['decimo_cuarto'], 2) }}</td>
                        <td style="padding:.6rem 1rem;text-align:right">${{ number_format($f['vacaciones'], 2) }}</td>
                        <td style="padding:.6rem 1rem;text-align:right">${{ number_format($f['fondos_reserva'], 2) }}</td>
                        <td style="padding:.6rem 1rem;text-align:right;font-weight:700;color:#161921">${{ number_format($f['total'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="padding:2rem;text-align:center;color:#8a949e">Sin empleados en dependencia o sin roles generados aún.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p style="margin-top:1rem;color:#6b7280;font-size:.82rem">
        Los décimos en modo "mensualizado" no aparecen aquí porque ya se pagaron en cada rol.
        Al pagar un beneficio, se genera su comprobante y el asiento que descarga la cuenta por pagar.
    </p>
</x-filament-panels::page>
