<?php
namespace App\Filament\Resources\EtiquetaResource\Pages;
use App\Filament\Resources\EtiquetaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditEtiqueta extends EditRecord { protected static string $resource = EtiquetaResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; } }
