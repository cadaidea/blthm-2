<?php
namespace App\Filament\Resources\GastoResource\Pages;
use App\Filament\Resources\GastoResource;
use App\Services\ContabilidadAuto;
use Filament\Resources\Pages\CreateRecord;
class CreateGasto extends CreateRecord
{
    protected static string $resource = GastoResource::class;
    protected function afterCreate(): void { ContabilidadAuto::gasto($this->record); }
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
