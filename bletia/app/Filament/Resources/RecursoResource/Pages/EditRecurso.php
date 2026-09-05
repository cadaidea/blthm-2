<?php
namespace App\Filament\Resources\RecursoResource\Pages;
use App\Filament\Resources\RecursoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditRecurso extends EditRecord
{
    protected static string $resource = RecursoResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
