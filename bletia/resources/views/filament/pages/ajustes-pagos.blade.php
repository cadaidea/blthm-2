<x-filament-panels::page>
    <form wire:submit="guardar">
        {{ $this->form }}
        <div style="margin-top:1.25rem;display:flex;gap:.75rem">
            <x-filament::button type="submit">Guardar</x-filament::button>
            <x-filament::button type="button" color="gray" wire:click="probar" wire:loading.attr="disabled">
                Probar conexión
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
