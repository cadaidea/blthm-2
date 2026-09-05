<x-filament-panels::page>
    <form wire:submit="guardar">
        {{ $this->form }}
        <div style="margin-top:16px;display:flex;gap:10px">
            <x-filament::button type="submit" icon="heroicon-o-check">Guardar</x-filament::button>
            <x-filament::button type="button" wire:click="probar" color="gray" icon="heroicon-o-shield-check">Probar firma (.p12)</x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
