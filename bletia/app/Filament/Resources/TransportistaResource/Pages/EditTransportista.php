<?php
namespace App\Filament\Resources\TransportistaResource\Pages;
use App\Filament\Resources\TransportistaResource;
use Filament\Actions; use Filament\Resources\Pages\EditRecord;
class EditTransportista extends EditRecord { protected static string $resource = TransportistaResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; } }
