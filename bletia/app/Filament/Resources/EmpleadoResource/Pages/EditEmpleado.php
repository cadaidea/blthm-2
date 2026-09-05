<?php
namespace App\Filament\Resources\EmpleadoResource\Pages;
use App\Filament\Resources\EmpleadoResource;
use Filament\Resources\Pages\EditRecord;

class EditEmpleado extends EditRecord
{
    protected static string $resource = EmpleadoResource::class;

    protected function afterSave(): void
    {
        EmpleadoResource::sincronizarAcceso($this->record, $this->form->getRawState());
    }
}
