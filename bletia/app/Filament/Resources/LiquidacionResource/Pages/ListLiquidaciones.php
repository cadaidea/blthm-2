<?php
namespace App\Filament\Resources\LiquidacionResource\Pages;
use App\Filament\Resources\LiquidacionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListLiquidaciones extends ListRecords
{
    protected static string $resource = LiquidacionResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()->label('Nueva liquidación')]; }
}
