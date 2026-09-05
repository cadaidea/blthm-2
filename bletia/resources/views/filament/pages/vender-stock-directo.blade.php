<x-filament-panels::page>
    @php($r = $this->getResumen())
    <form wire:submit="vender">
        <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start">
            <div>{{ $this->form }}</div>
            <aside style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px;position:sticky;top:16px">
                <div style="font-weight:700;font-size:14px;margin-bottom:10px;">Resumen</div>
                <div style="font-size:13px;">
                    <div style="display:flex;justify-content:space-between"><span>Total venta</span><strong>${{ number_format($r['total'],2) }}</strong></div>
                    <div style="display:flex;justify-content:space-between;margin-top:4px;"><span>Total pagado</span><span>${{ number_format($r['pagado'],2) }}</span></div>
                    <div style="display:flex;justify-content:space-between;font-weight:700;margin-top:8px;padding-top:8px;border-top:2px solid #d1d5db;color:{{ abs($r['diff']) > 0.01 ? '#c0392b' : '#1f8b4c' }}">
                        <span>{{ abs($r['diff']) > 0.01 ? 'Diferencia' : 'Cuadrado' }}</span>
                        <span>${{ number_format($r['diff'],2) }}</span>
                    </div>
                </div>
                <div style="margin-top:14px;font-size:12px;color:#8a929c;">El total de los pagos debe cuadrar exactamente con el total de la venta antes de emitir.</div>
            </aside>
        </div>
        <div style="margin-top:18px;display:flex;gap:10px">
            <x-filament::button type="submit" size="lg" icon="heroicon-o-check">Emitir comprobante</x-filament::button>
            <x-filament::button tag="a" :href="\App\Filament\Resources\VentaResource::getUrl()" color="gray" size="lg">Cancelar</x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
