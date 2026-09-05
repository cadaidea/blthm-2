<?php
namespace App\Filament\Resources\CuentaMapeoResource\Pages;
use App\Filament\Resources\CuentaMapeoResource;
use App\Services\ContabilidadAuto;
use Filament\Resources\Pages\EditRecord;
class EditCuentaMapeo extends EditRecord
{
    protected static string $resource = CuentaMapeoResource::class;
    protected function afterSave(): void { ContabilidadAuto::olvidar(); }
}
