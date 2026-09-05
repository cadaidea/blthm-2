<?php
namespace App\Filament\Resources\EditorResource\Pages;
use App\Filament\Resources\EditorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListEditor extends ListRecords { protected static string $resource = EditorResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; } }
