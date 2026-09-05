<?php
namespace App\Filament\Resources\CampaniaResource\Pages;
use App\Filament\Resources\CampaniaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditCampania extends EditRecord { protected static string $resource = CampaniaResource::class;
    protected function mutateFormDataBeforeSave(array $data): array {
        if (in_array($this->record->estado, ['borrador', 'programada'], true)) {
            $data['estado'] = !empty($data['programada_at']) ? 'programada' : 'borrador';
        }
        return $data;
    }
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; } }
