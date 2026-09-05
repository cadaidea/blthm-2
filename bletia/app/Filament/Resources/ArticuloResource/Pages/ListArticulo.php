<?php
namespace App\Filament\Resources\ArticuloResource\Pages;
use Filament\Actions\CreateAction;
use App\Filament\Resources\ArticuloResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListArticulo extends ListRecords {
    protected static string $resource = ArticuloResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()]; }
}
