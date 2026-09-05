<?php
namespace App\Filament\Resources\CuponResource\Pages;
use Filament\Actions\CreateAction;
use App\Filament\Resources\CuponResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListCupones extends ListRecords { protected static string $resource = CuponResource::class; protected function getHeaderActions(): array { return [CreateAction::make()]; } }
