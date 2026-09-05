<?php
namespace App\Filament\Resources\MateriaPrimaResource\Pages;
use App\Filament\Resources\MateriaPrimaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListMateriaPrimas extends ListRecords
{
    protected static string $resource = MateriaPrimaResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
