<x-filament-panels::page>
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:1.25rem 1.5rem;">
        <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:.5rem;margin-bottom:1rem">
            <div>
                <div style="font-size:.75rem;color:#8a949e;text-transform:uppercase;letter-spacing:.05em;font-weight:600">Asiento {{ $record->numero ?: ('#' . $record->id) }}</div>
                <div style="font-size:1.1rem;font-weight:700;color:#161921">{{ $record->glosa }}</div>
            </div>
            <div style="text-align:right">
                <div style="font-size:.85rem;color:#64748b">{{ \Illuminate\Support\Carbon::parse($record->fecha)->format('d/m/Y') }}</div>
                <span style="display:inline-block;margin-top:.25rem;padding:.15rem .6rem;border-radius:999px;font-size:.75rem;font-weight:600;background:{{ $record->estado === 'anulado' ? '#fef2f2' : '#f0fdf4' }};color:{{ $record->estado === 'anulado' ? '#b91c1c' : '#15803d' }}">
                    {{ ucfirst($record->estado) }}
                </span>
            </div>
        </div>

        <table style="width:100%;border-collapse:collapse;font-size:.9rem">
            <thead>
                <tr style="background:#f8fafc;text-align:left">
                    <th style="padding:.6rem .75rem;color:#475569;font-weight:600">Cuenta</th>
                    <th style="padding:.6rem .75rem;color:#475569;font-weight:600;text-align:right">Debe</th>
                    <th style="padding:.6rem .75rem;color:#475569;font-weight:600;text-align:right">Haber</th>
                </tr>
            </thead>
            <tbody>
                @foreach($record->lineas as $l)
                    <tr style="border-top:1px solid #f1f5f9">
                        <td style="padding:.55rem .75rem">{{ $l->cuenta?->codigo }} · {{ $l->cuenta?->nombre }}</td>
                        <td style="padding:.55rem .75rem;text-align:right">{{ (float)$l->debe > 0 ? '$' . number_format($l->debe, 2) : '' }}</td>
                        <td style="padding:.55rem .75rem;text-align:right">{{ (float)$l->haber > 0 ? '$' . number_format($l->haber, 2) : '' }}</td>
                    </tr>
                @endforeach
                <tr style="border-top:2px solid #e2e8f0;font-weight:700;background:#fafafa">
                    <td style="padding:.6rem .75rem;text-align:right">Totales</td>
                    <td style="padding:.6rem .75rem;text-align:right">${{ number_format($record->debe, 2) }}</td>
                    <td style="padding:.6rem .75rem;text-align:right">${{ number_format($record->haber, 2) }}</td>
                </tr>
            </tbody>
        </table>

        @if($record->origen_tipo)
            <p style="margin-top:1rem;color:#6b7280;font-size:.82rem">Origen: {{ $record->origen }} · {{ $record->origen_tipo }} #{{ $record->origen_id }}</p>
        @endif
    </div>
</x-filament-panels::page>
