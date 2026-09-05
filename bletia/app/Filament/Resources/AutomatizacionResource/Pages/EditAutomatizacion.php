<?php
namespace App\Filament\Resources\AutomatizacionResource\Pages;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\AutomatizacionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditAutomatizacion extends EditRecord
{
    protected static string $resource = AutomatizacionResource::class;
    protected function getHeaderActions(): array { return [DeleteAction::make()]; }
}
