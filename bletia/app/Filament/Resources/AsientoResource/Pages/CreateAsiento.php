<?php
namespace App\Filament\Resources\AsientoResource\Pages;
use App\Filament\Resources\AsientoResource;
use App\Services\Contabilidad;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class CreateAsiento extends CreateRecord
{
    protected static string $resource = AsientoResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $lineas = [];
        foreach ($data['lineas_tmp'] ?? [] as $l) {
            $lineas[] = ['cuenta' => $l['cuenta_id'], 'debe' => $l['debe'] ?? 0, 'haber' => $l['haber'] ?? 0];
        }
        try {
            return Contabilidad::asentar($data['fecha'], $data['glosa'], $lineas, ['origen' => 'manual']);
        } catch (\Throwable $e) {
            Notification::make()->danger()->title('No se pudo guardar')->body($e->getMessage())->persistent()->send();
            throw ValidationException::withMessages(['data.glosa' => $e->getMessage()]);
        }
    }

    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
