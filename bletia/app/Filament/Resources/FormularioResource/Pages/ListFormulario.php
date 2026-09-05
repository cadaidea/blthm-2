<?php
namespace App\Filament\Resources\FormularioResource\Pages;
use App\Filament\Resources\FormularioResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListFormulario extends ListRecords { protected static string $resource = FormularioResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; } }
