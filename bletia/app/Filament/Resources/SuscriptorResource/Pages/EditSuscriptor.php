<?php
namespace App\Filament\Resources\SuscriptorResource\Pages;
use App\Filament\Resources\SuscriptorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditSuscriptor extends EditRecord { protected static string $resource = SuscriptorResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; } }
