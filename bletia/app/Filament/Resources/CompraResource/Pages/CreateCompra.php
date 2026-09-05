<?php
namespace App\Filament\Resources\CompraResource\Pages;
use App\Filament\Resources\CompraResource;
use Filament\Resources\Pages\CreateRecord;
class CreateCompra extends CreateRecord
{
    protected static string $resource = CompraResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['creado_por'] = auth()->id();
        $data['estado'] = 'creada';
        return $data;
    }
    protected function afterCreate(): void
    {
        $this->record->items()->each(function ($it) {
            $cant = (float) $it->cantidad;
            $costo = (float) $it->costo_unitario;
            $it->update(['subtotal' => round($cant * $costo, 2)]);
        });
        // si se subió foto nueva para alguna variante, guardarla en el catálogo
        foreach ($this->form->getRawState()['items'] ?? [] as $itData) {
            if (! empty($itData['variante_id']) && ! empty($itData['foto_nueva'])) {
                $foto = is_array($itData['foto_nueva']) ? (collect($itData['foto_nueva'])->first()) : $itData['foto_nueva'];
                if ($foto) \App\Models\Variante::where('id', $itData['variante_id'])->update(['foto' => $foto]);
            }
        }
        $sub = (float) $this->record->items()->sum('subtotal');
        $iva = (float) $this->record->items->sum(fn ($it) => round((float) $it->subtotal * (float) $it->iva_rate / 100, 2));
        $this->record->update(['subtotal' => round($sub, 2), 'iva' => round($iva, 2), 'total' => round($sub + $iva, 2)]);
    }
}
