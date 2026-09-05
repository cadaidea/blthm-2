<?php

namespace App\Filament\Resources\MovimientoStockResource\Pages;

use App\Filament\Resources\MovimientoStockResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateMovimientoStock extends CreateRecord
{
    protected static string $resource = MovimientoStockResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (($data['tipo'] ?? null) === 'transferencia' && empty($data['local_destino_id'])) {
            Notification::make()->danger()->title('Falta la bodega destino')->body('Una transferencia necesita bodega de origen y destino.')->send();
            $this->halt();
        }
        return $data;
    }
}
