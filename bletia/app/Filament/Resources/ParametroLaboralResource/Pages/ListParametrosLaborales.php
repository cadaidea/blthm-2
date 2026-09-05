<?php
namespace App\Filament\Resources\ParametroLaboralResource\Pages;
use App\Filament\Resources\ParametroLaboralResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListParametrosLaborales extends ListRecords
{
    protected static string $resource = ParametroLaboralResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
