<?php
namespace App\Filament\Resources\DespachoResource\Pages;
use App\Filament\Resources\DespachoResource;
use Filament\Actions; use Filament\Resources\Pages\EditRecord;
class EditDespacho extends EditRecord { protected static string $resource = DespachoResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; } }
