<x-filament-panels::page>
    @php($pr = $this->propios())
    @php($wo = $this->woo())

    <style>
        .to-wrap{font-family:'Inter',ui-sans-serif,system-ui,sans-serif}
        .to-sec{font-weight:700;font-size:13px;letter-spacing:.02em;text-transform:uppercase;color:#8a93a6;margin:0 0 14px}
        .to-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:14px;margin-bottom:28px}
        .to-card{background:#fff;border:1px solid #edeff3;border-radius:14px;padding:16px;box-shadow:0 1px 2px rgba(16,24,40,.04)}
        .to-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
        .to-badge{display:inline-flex;align-items:center;gap:6px;font-size:11px;font-weight:700;padding:4px 10px;border-radius:8px}
        .to-total{font-weight:800;font-size:18px;color:#1d2433}
        .to-row{display:flex;align-items:center;gap:8px;font-size:13px;color:#475067;margin-bottom:6px}
        .to-row svg{width:15px;height:15px;color:#9aa2b1;flex-shrink:0}
        .to-dest{display:inline-flex;align-items:center;gap:6px;font-size:11.5px;font-weight:650;padding:4px 10px;border-radius:8px;margin:8px 0 12px}
        .to-empty{font-size:13px;color:#b6bdc9;padding:26px;text-align:center;background:#f7f8fa;border-radius:12px}
    </style>

    <div class="to-wrap">
        <div class="to-sec">Tienda propia · Bletia.ec</div>
        @if(count($pr))
        <div class="to-grid">
            @foreach($pr as $c)
                <div class="to-card">
                    <div class="to-top">
                        <span class="to-badge" style="background:{{ $c['color'] }}1a;color:{{ $c['color'] }}"><x-filament::icon icon="heroicon-o-globe-alt" style="width:13px;height:13px" /> {{ $c['origen'] }}</span>
                        <span class="to-total">${{ number_format($c['total'],2) }}</span>
                    </div>
                    <div class="to-row"><x-filament::icon icon="heroicon-o-user" /> {{ $c['cliente'] }}</div>
                    <div class="to-row"><x-filament::icon icon="heroicon-o-cube" /> {{ $c['items'] }} ítem(s) · {{ $c['fecha'] }}</div>
                    <div class="to-row"><x-filament::icon icon="heroicon-o-credit-card" /> {{ $c['metodo'] }}</div>
                    <div class="to-dest" style="background:{{ $c['destino']==='A fabricar' ? '#fef0c7;color:#b54708' : '#d1fadf;color:#027a48' }}">
                        <x-filament::icon :icon="$c['destino']==='A fabricar' ? 'heroicon-o-wrench-screwdriver' : 'heroicon-o-truck'" style="width:14px;height:14px" /> {{ $c['destino'] }}
                    </div>
                    <x-filament::button wire:click="tomarPropio({{ $c['id'] }})" wire:loading.attr="disabled" icon="heroicon-o-hand-raised" style="width:100%">Tomar</x-filament::button>
                </div>
            @endforeach
        </div>
        @else
            <div class="to-empty" style="margin-bottom:28px">No hay pedidos de la tienda propia por tomar.</div>
        @endif

        <div class="to-sec">WooCommerce (API)</div>
        @if(count($wo))
        <div class="to-grid">
            @foreach($wo as $c)
                <div class="to-card">
                    <div class="to-top">
                        <span class="to-badge" style="background:{{ $c['color'] }}1a;color:{{ $c['color'] }}"><x-filament::icon icon="heroicon-o-shopping-bag" style="width:13px;height:13px" /> {{ $c['origen'] }}</span>
                        <span class="to-total">${{ number_format($c['total'],2) }}</span>
                    </div>
                    <div class="to-row"><x-filament::icon icon="heroicon-o-user" /> {{ $c['cliente'] }}</div>
                    <div class="to-row"><x-filament::icon icon="heroicon-o-cube" /> {{ $c['items'] }} ítem(s) · #{{ $c['numero'] }} · {{ $c['fecha'] }}</div>
                    <div class="to-row"><x-filament::icon icon="heroicon-o-credit-card" /> {{ $c['metodo'] }}</div>
                    <x-filament::button wire:click="tomarWoo({{ $c['id'] }})" wire:loading.attr="disabled" color="primary" icon="heroicon-o-hand-raised" style="width:100%;margin-top:8px">Tomar</x-filament::button>
                </div>
            @endforeach
        </div>
        @else
            <div class="to-empty">No hay pedidos de WooCommerce por tomar.</div>
        @endif
    </div>
</x-filament-panels::page>
