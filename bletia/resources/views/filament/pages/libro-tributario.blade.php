<x-filament-panels::page>
    <form wire:submit="calcular">
        {{ $this->form }}
        <div style="margin-top:1rem;display:flex;gap:.6rem;flex-wrap:wrap">
            <x-filament::button type="submit" color="gray" icon="heroicon-o-arrow-path">Actualizar</x-filament::button>
            <x-filament::button wire:click="descargar" icon="heroicon-o-arrow-down-tray">Descargar Excel</x-filament::button>
        </div>
    </form>

    @if($resumen)
        @php($r = $resumen)
        <div style="margin-top:1.5rem;display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:.9rem">
            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:1rem 1.15rem">
                <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:#8a949e;font-weight:600">IVA cobrado (ventas)</div>
                <div style="font-size:1.5rem;font-weight:700;color:#161921;margin-top:.2rem">${{ number_format($r['iva_cobrado'], 2) }}</div>
            </div>
            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:1rem 1.15rem">
                <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:#8a949e;font-weight:600">IVA pagado (compras)</div>
                <div style="font-size:1.5rem;font-weight:700;color:#161921;margin-top:.2rem">${{ number_format($r['iva_pagado'], 2) }}</div>
            </div>
            <div style="background:{{ $r['iva_a_pagar'] >= 0 ? '#fef2f2' : '#f0fdf4' }};border:1px solid {{ $r['iva_a_pagar'] >= 0 ? '#fecaca' : '#bbf7d0' }};border-radius:12px;padding:1rem 1.15rem">
                <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:#8a949e;font-weight:600">IVA a pagar</div>
                <div style="font-size:1.5rem;font-weight:700;color:{{ $r['iva_a_pagar'] >= 0 ? '#b91c1c' : '#15803d' }};margin-top:.2rem">${{ number_format($r['iva_a_pagar'], 2) }}</div>
            </div>
        </div>

        <div style="margin-top:1.25rem;background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden">
            <table style="width:100%;border-collapse:collapse;font-size:.9rem">
                <thead>
                    <tr style="background:#f8fafc;text-align:left">
                        <th style="padding:.7rem 1rem;color:#475569;font-weight:600"></th>
                        <th style="padding:.7rem 1rem;color:#475569;font-weight:600;text-align:right">Base</th>
                        <th style="padding:.7rem 1rem;color:#475569;font-weight:600;text-align:right">IVA</th>
                        <th style="padding:.7rem 1rem;color:#475569;font-weight:600;text-align:right">N°</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="border-top:1px solid #f1f5f9">
                        <td style="padding:.65rem 1rem;font-weight:600">Ventas · a RUC</td>
                        <td style="padding:.65rem 1rem;text-align:right">${{ number_format($r['ventas']['ruc']['base'], 2) }}</td>
                        <td style="padding:.65rem 1rem;text-align:right">${{ number_format($r['ventas']['ruc']['iva'], 2) }}</td>
                        <td style="padding:.65rem 1rem;text-align:right;color:#64748b">—</td>
                    </tr>
                    <tr style="border-top:1px solid #f1f5f9">
                        <td style="padding:.65rem 1rem;font-weight:600">Ventas · a cédula</td>
                        <td style="padding:.65rem 1rem;text-align:right">${{ number_format($r['ventas']['cedula']['base'], 2) }}</td>
                        <td style="padding:.65rem 1rem;text-align:right">${{ number_format($r['ventas']['cedula']['iva'], 2) }}</td>
                        <td style="padding:.65rem 1rem;text-align:right;color:#64748b">—</td>
                    </tr>
                    <tr style="border-top:1px solid #f1f5f9">
                        <td style="padding:.65rem 1rem;font-weight:600">Ventas · consumidor final</td>
                        <td style="padding:.65rem 1rem;text-align:right">${{ number_format($r['ventas']['consumidor_final']['base'], 2) }}</td>
                        <td style="padding:.65rem 1rem;text-align:right">${{ number_format($r['ventas']['consumidor_final']['iva'], 2) }}</td>
                        <td style="padding:.65rem 1rem;text-align:right;color:#64748b">{{ $r['n_ventas'] }} docs</td>
                    </tr>
                    <tr style="border-top:2px solid #e2e8f0;background:#fafafa">
                        <td style="padding:.65rem 1rem;font-weight:700">Compras · con RUC</td>
                        <td style="padding:.65rem 1rem;text-align:right">${{ number_format($r['compras']['ruc']['base'], 2) }}</td>
                        <td style="padding:.65rem 1rem;text-align:right">${{ number_format($r['compras']['ruc']['iva'], 2) }}</td>
                        <td style="padding:.65rem 1rem;text-align:right;color:#64748b">{{ $r['n_compras'] }} docs</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p style="margin-top:1rem;color:#6b7280;font-size:.85rem">
            Periodo {{ $r['desde'] }} a {{ $r['hasta'] }}. El Excel incluye 4 hojas: Ventas, Compras, Bancarización y Resumen IVA.
            La bancarización lista pagos no-efectivo iguales o mayores a $1.000.
        </p>
    @endif
</x-filament-panels::page>
