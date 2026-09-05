<?php
namespace App\Filament\Resources\FormularioContactoResource\Pages;
use App\Filament\Resources\FormularioContactoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
class EditFormularioContacto extends EditRecord {
    protected static string $resource = FormularioContactoResource::class;
    protected function getHeaderActions(): array { return [DeleteAction::make()]; }
}
