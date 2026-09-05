<x-filament-panels::page>
    <form wire:submit="guardar">
        {{ $this->form }}
        <div style="margin-top:1.25rem">
            <x-filament::button type="submit">Guardar credenciales</x-filament::button>
        </div>
    </form>
    <p style="margin-top:1rem;color:#6b7280;font-size:.875rem">
        Genera las llaves en seridea.ec → WooCommerce → Ajustes → Avanzado → REST API → "Add key"
        (permiso de Lectura). Pega Consumer key/secret aquí, prueba la conexión y luego importa.
        Los pedidos importados se ven en ERP → "Pedidos online (Woo)".
    </p>
</x-filament-panels::page>
