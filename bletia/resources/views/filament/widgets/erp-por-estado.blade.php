<x-filament-widgets::widget>
    <x-filament::section heading="Pedidos especiales por estado">
        <div style="display:flex;flex-wrap:wrap;gap:10px">
            @foreach($this->getEstados() as $e)
                <div style="flex:1;min-width:120px;border:1px solid var(--gray-200,#e5e7eb);border-radius:10px;padding:12px">
                    <div style="font-size:12px;color:#6b7280">{{ $e['estado'] }}</div>
                    <div style="font-size:22px;font-weight:700">{{ $e['n'] }}</div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
