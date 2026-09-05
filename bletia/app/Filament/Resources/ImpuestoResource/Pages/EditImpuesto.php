<?php
namespace App\Filament\Resources\ImpuestoResource\Pages;
use App\Filament\Resources\ImpuestoResource;
use App\Support\Impuestos;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditImpuesto extends EditRecord
{
    protected static string $resource = ImpuestoResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
    protected function afterSave(): void { Impuestos::olvidar(); }
}
