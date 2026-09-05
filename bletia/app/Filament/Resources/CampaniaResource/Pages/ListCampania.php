<?php
namespace App\Filament\Resources\CampaniaResource\Pages;
use Filament\Actions\CreateAction;
use App\Filament\Resources\CampaniaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListCampania extends ListRecords { protected static string $resource = CampaniaResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()]; } }
