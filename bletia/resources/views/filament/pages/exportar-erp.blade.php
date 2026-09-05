<x-filament-panels::page>
    <form wire:submit="generar">
        {{ $this->form }}
        <div style="margin-top:1.25rem">
            <x-filament::button type="submit" icon="heroicon-o-arrow-down-tray">Generar Excel</x-filament::button>
        </div>
    </form>
    <p style="margin-top:1rem;color:#6b7280;font-size:.875rem">
        Genera un archivo .xlsx con una pestaña por módulo. Si dejas las fechas vacías, exporta todo.
    </p>
</x-filament-panels::page>
