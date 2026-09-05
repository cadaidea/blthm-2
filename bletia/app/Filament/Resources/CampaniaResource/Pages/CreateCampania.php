<?php
namespace App\Filament\Resources\CampaniaResource\Pages;
use App\Filament\Resources\CampaniaResource;
use Filament\Resources\Pages\CreateRecord;
class CreateCampania extends CreateRecord { protected static string $resource = CampaniaResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array {
        $data['estado'] = !empty($data['programada_at']) ? 'programada' : 'borrador';
        return $data;
    } }
