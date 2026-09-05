<?php
namespace App\Filament\Resources\IncentivoResource\Pages;
use App\Filament\Resources\IncentivoResource;
use App\Services\IncentivosContable;
use Filament\Resources\Pages\CreateRecord;
class CreateIncentivo extends CreateRecord
{
    protected static string $resource = IncentivoResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['estado'] = 'pagado';
        $data['creado_por'] = auth()->id();
        return $data;
    }
    protected function afterCreate(): void
    {
        IncentivosContable::asentar($this->record->fresh('empleado'));
    }
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
