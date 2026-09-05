<?php
namespace App\Filament\Resources\VacacionTomadaResource\Pages;
use App\Filament\Resources\VacacionTomadaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListVacacionTomadas extends ListRecords
{
    protected static string $resource = VacacionTomadaResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()->label('Registrar vacaciones')]; }
}
