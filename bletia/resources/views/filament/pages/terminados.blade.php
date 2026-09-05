<x-filament-panels::page>
    @php($items = $this->getItems())

    <div style="display:flex;flex-direction:column;gap:10px">
        @forelse($items as $it)
            <a href="{{ $it['url'] }}" style="text-decoration:none;color:inherit">
                <div style="background:#fff;border:1px solid #eee;border-radius:14px;padding:16px 18px;display:flex;justify-content:space-between;align-items:center;gap:14px;transition:box-shadow .15s">
                    <div style="display:flex;flex-direction:column;gap:4px;min-width:0">
                        <div style="display:flex;align-items:center;gap:8px">
                            <span style="font-weight:700;color:#161921">{{ $it['folio'] }}</span>
                            <span style="font-size:.72rem;font-weight:600;padding:2px 9px;border-radius:999px;background:{{ $it['origen'] === 'cliente' ? '#e8f3ff' : ($it['origen'] === 'proveedor' ? '#fff4e5' : '#eafaf0') }};color:{{ $it['origen'] === 'cliente' ? '#2563eb' : ($it['origen'] === 'proveedor' ? '#b45309' : '#15803d') }}">
                                {{ $it['origen'] === 'cliente' ? 'Cliente' : ($it['origen'] === 'proveedor' ? 'Proveedor' : 'Abastecimiento') }}
                            </span>
                        </div>
                        <div style="color:#444;font-size:.92rem">{{ $it['titulo'] }}</div>
                        @if(!empty($it['detalle']))
                            <div style="color:#888;font-size:.82rem">{{ $it['detalle'] }}</div>
                        @endif
                    </div>
                    <div style="text-align:right;color:#888;font-size:.82rem;white-space:nowrap">
                        @if($it['fecha'])
                            {{ $it['fecha']->format('d/m/Y H:i') }}
                        @else
                            —
                        @endif
                    </div>
                </div>
            </a>
        @empty
            <div style="background:#fff;border:1px solid #eee;border-radius:14px;padding:30px;text-align:center;color:#888">
                Nada terminado todavía.
            </div>
        @endforelse
    </div>
</x-filament-panels::page>
