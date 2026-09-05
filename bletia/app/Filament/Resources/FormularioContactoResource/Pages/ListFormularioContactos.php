<?php
namespace App\Filament\Resources\FormularioContactoResource\Pages;
use App\Filament\Resources\FormularioContactoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListFormularioContactos extends ListRecords {
    protected static string $resource = FormularioContactoResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()]; }
}
