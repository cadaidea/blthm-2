<?php

namespace App\Filament\Resources\CategoriaResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\CategoriaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCategorias extends ListRecords
{
    protected static string $resource = CategoriaResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
