<x-filament-panels::page>
    <form wire:submit="guardar">
        {{ $this->form }}
        <div style="margin-top:16px">
            <x-filament::button type="submit" icon="heroicon-o-check">Guardar materiales</x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
