<x-filament-panels::page>
    <div style="overflow-x:auto;border-radius:12px;border:1px solid #e5e7eb;background:#fff">
        <table style="width:100%;font-size:.875rem;border-collapse:collapse">
            <thead style="background:#f9fafb">
                <tr>
                    <th style="padding:.75rem 1rem;text-align:left;font-weight:600">Producto</th>
                    @foreach($this->getLocales() as $l)
                        <th style="padding:.75rem 1rem;text-align:center;font-weight:600">{{ $l->nombre }}</th>
                    @endforeach
                    <th style="padding:.75rem 1rem;text-align:center;font-weight:600">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($this->getFilas() as $f)
                    <tr style="font-weight:500;background:#fafafa;border-top:1px solid #f3f4f6">
                        <td style="padding:.75rem 1rem">{{ $f['nombre'] }}</td>
                        @foreach($this->getLocales() as $l)
                            <td style="padding:.75rem 1rem;text-align:center;{{ $f['por'][$l->id]['bajo'] ? 'color:#dc2626;font-weight:600' : '' }}">
                                {{ $f['por'][$l->id]['cant'] }}
                            </td>
                        @endforeach
                        <td style="padding:.75rem 1rem;text-align:center;font-weight:600">{{ $f['total'] }}</td>
                    </tr>
                    @if(!empty($f['variantes']) && $f['variantes']->isNotEmpty())
                        @foreach($f['variantes'] as $v)
                            <tr style="color:#6b7280;border-top:1px solid #f3f4f6">
                                <td style="padding:.5rem 1rem .5rem 2rem;font-size:.75rem">↳ {{ $v['label'] ?: 'Combinación sin opciones' }}</td>
                                @foreach($this->getLocales() as $l)
                                    <td style="padding:.5rem 1rem;text-align:center;font-size:.75rem;{{ $v['por'][$l->id]['bajo'] ? 'color:#dc2626;font-weight:600' : '' }}">
                                        {{ $v['por'][$l->id]['cant'] }}
                                    </td>
                                @endforeach
                                <td style="padding:.5rem 1rem;text-align:center;font-size:.75rem;font-weight:500">{{ $v['total'] }}</td>
                            </tr>
                        @endforeach
                    @endif
                @empty
                    <tr><td style="padding:1.5rem 1rem;text-align:center;color:#6b7280" colspan="99">Sin productos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <p style="font-size:.75rem;color:#6b7280;margin-top:.5rem">En rojo: stock igual o bajo el mínimo definido. Las filas con "↳" muestran el detalle por combinación (tapiz/lado/madera).</p>
</x-filament-panels::page>
