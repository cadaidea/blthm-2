<x-filament-panels::page>
    @php($r = $this->getResumen())
    <form wire:submit="crear">
        <div style="display:grid;grid-template-columns:1fr 340px;gap:18px;align-items:start">
            <div>{{ $this->form }}</div>

            <aside style="position:sticky;top:80px;background:var(--gray-50,#f9fafb);border:1px solid var(--gray-200,#e5e7eb);border-radius:14px;padding:16px">
                <div style="font-weight:700;font-size:15px;margin-bottom:10px">Resumen</div>

                @forelse($r['filas'] as $f)
                    <div style="padding:8px 0;border-bottom:1px dashed var(--gray-200,#e5e7eb)">
                        <div style="font-weight:600;font-size:13px">{{ $f['nombre'] }}</div>
                        <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--gray-500,#6b7280)">
                            <span>{{ $f['cant'] }} × ${{ number_format($f['pvp'],2) }}</span>
                            <span>Ajuste: {{ $f['ajuste'] }}</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:13px;margin-top:2px">
                            <span>Precio final (u.): ${{ number_format($f['unit'],2) }}</span>
                            <strong>${{ number_format($f['sub'],2) }}</strong>
                        </div>
                    </div>
                @empty
                    <div style="font-size:13px;color:var(--gray-500,#6b7280)">Agrega ítems para ver el detalle.</div>
                @endforelse

                <div style="margin-top:12px;font-size:13px">
                    <div style="display:flex;justify-content:space-between"><span>Subtotal (base)</span><span>${{ number_format($r['base'],2) }}</span></div>
                    <div style="display:flex;justify-content:space-between"><span>IVA 15%</span><span>${{ number_format($r['iva'],2) }}</span></div>
                    <div style="display:flex;justify-content:space-between;font-weight:800;font-size:16px;margin-top:6px;padding-top:6px;border-top:2px solid var(--gray-300,#d1d5db)">
                        <span>Total a pagar</span><span>${{ number_format($r['total'],2) }}</span>
                    </div>
                </div>
            </aside>
        </div>

        <div style="margin-top:18px;display:flex;gap:10px">
            <x-filament::button type="submit" size="lg" icon="heroicon-o-check">Registrar venta</x-filament::button>
            <x-filament::button tag="a" :href="\App\Filament\Resources\PedidoEspecialResource::getUrl()" color="gray" size="lg">Cancelar</x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
