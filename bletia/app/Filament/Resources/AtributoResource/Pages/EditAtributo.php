<?php
namespace App\Filament\Resources\AtributoResource\Pages;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\AtributoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditAtributo extends EditRecord { protected static string $resource = AtributoResource::class;
    protected function getHeaderActions(): array { return [DeleteAction::make()]; } }
