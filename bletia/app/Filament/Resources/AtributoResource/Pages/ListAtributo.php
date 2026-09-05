<?php
namespace App\Filament\Resources\AtributoResource\Pages;
use Filament\Actions\CreateAction;
use App\Filament\Resources\AtributoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListAtributo extends ListRecords { protected static string $resource = AtributoResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()]; } }
