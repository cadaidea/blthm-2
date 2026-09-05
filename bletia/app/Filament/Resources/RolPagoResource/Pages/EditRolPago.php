<?php
namespace App\Filament\Resources\RolPagoResource\Pages;
use App\Filament\Resources\RolPagoResource;
use App\Services\Nomina;
use Filament\Resources\Pages\EditRecord;
class EditRolPago extends EditRecord
{
    protected static string $resource = RolPagoResource::class;
    protected function afterSave(): void
    {
        $this->record->load('empleado');
        Nomina::aplicar($this->record);
        $this->record->saveQuietly();
    }
}
