<?php
namespace App\Filament\Resources\EditorResource\Pages;
use App\Filament\Resources\EditorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditEditor extends EditRecord { protected static string $resource = EditorResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; } }
