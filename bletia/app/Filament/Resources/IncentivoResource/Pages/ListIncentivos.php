<?php
namespace App\Filament\Resources\IncentivoResource\Pages;
use App\Filament\Resources\IncentivoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListIncentivos extends ListRecords
{
    protected static string $resource = IncentivoResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()->label('Registrar incentivo')]; }
}
