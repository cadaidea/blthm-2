<?php
namespace App\Filament\Resources\AutomatizacionResource\Pages;
use Filament\Actions\CreateAction;
use App\Filament\Resources\AutomatizacionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListAutomatizaciones extends ListRecords
{
    protected static string $resource = AutomatizacionResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()]; }
}
