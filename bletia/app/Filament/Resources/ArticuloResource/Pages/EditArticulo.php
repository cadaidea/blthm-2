<?php
namespace App\Filament\Resources\ArticuloResource\Pages;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\ArticuloResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditArticulo extends EditRecord {
    protected static string $resource = ArticuloResource::class;
    protected function getHeaderActions(): array { return [DeleteAction::make()]; }
}
