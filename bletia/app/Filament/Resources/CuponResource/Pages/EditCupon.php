<?php
namespace App\Filament\Resources\CuponResource\Pages;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\CuponResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditCupon extends EditRecord { protected static string $resource = CuponResource::class; protected function getHeaderActions(): array { return [DeleteAction::make()]; } }
