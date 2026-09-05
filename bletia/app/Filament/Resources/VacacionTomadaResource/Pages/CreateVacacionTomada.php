<?php
namespace App\Filament\Resources\VacacionTomadaResource\Pages;
use App\Filament\Resources\VacacionTomadaResource;
use Filament\Resources\Pages\CreateRecord;
class CreateVacacionTomada extends CreateRecord
{
    protected static string $resource = VacacionTomadaResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['pendientes_info']);
        $data['estado'] = 'registrada';
        $data['creado_por'] = auth()->id();
        return $data;
    }
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
