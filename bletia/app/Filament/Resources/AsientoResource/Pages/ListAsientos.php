<?php
namespace App\Filament\Resources\AsientoResource\Pages;
use App\Filament\Resources\AsientoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListAsientos extends ListRecords
{
    protected static string $resource = AsientoResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()->label('Nuevo asiento')]; }
}
