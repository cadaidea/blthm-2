<?php
namespace App\Filament\Resources\RolPagoResource\Pages;
use App\Filament\Resources\RolPagoResource;
use App\Services\Nomina;
use App\Models\RolPago;
use Filament\Resources\Pages\CreateRecord;
class CreateRolPago extends CreateRecord
{
    protected static string $resource = RolPagoResource::class;
    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $rol = new RolPago($data);
        $rol->estado = 'borrador';
        $rol->save();
        $rol->load('empleado');
        Nomina::aplicar($rol);
        $rol->save();
        return $rol;
    }
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
