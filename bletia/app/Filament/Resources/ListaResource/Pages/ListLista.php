<?php
namespace App\Filament\Resources\ListaResource\Pages;
use App\Filament\Resources\ListaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListLista extends ListRecords { protected static string $resource = ListaResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; } }
