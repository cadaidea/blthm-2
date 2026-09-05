<?php
namespace App\Filament\Resources\LiquidacionResource\Pages;
use App\Filament\Resources\LiquidacionResource;
use App\Services\PagosBeneficio;
use App\Models\Liquidacion;
use Filament\Resources\Pages\CreateRecord;
class CreateLiquidacion extends CreateRecord
{
    protected static string $resource = LiquidacionResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['total'] = round((float)($data['decimo_tercero']??0) + (float)($data['decimo_cuarto']??0) + (float)($data['vacaciones']??0) + (float)($data['fondos_reserva']??0) + (float)($data['indemnizacion']??0) + (float)($data['bonificacion_desahucio']??0) + (float)($data['otros']??0) - (float)($data['descuentos']??0), 2);
        $data['estado'] = 'registrada';
        $data['creado_por'] = auth()->id();
        return $data;
    }
    protected function afterCreate(): void
    {
        PagosBeneficio::asentarLiquidacion($this->record->fresh('empleado'));
        // marca al empleado como inactivo con su fecha de salida
        if ($this->record->empleado) {
            $this->record->empleado->update(['activo' => false, 'fecha_salida' => $this->record->fecha_salida]);
        }
    }
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
