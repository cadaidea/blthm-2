<?php
namespace App\Filament\Resources\EmpleadoResource\Pages;
use App\Filament\Resources\EmpleadoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmpleado extends CreateRecord
{
    protected static string $resource = EmpleadoResource::class;

    protected function afterCreate(): void
    {
        EmpleadoResource::sincronizarAcceso($this->record, $this->form->getRawState());
    }
}
