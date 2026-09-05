<?php
namespace App\Filament\Resources\RolPagoResource\Pages;
use App\Filament\Resources\RolPagoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListRolesPago extends ListRecords
{
    protected static string $resource = RolPagoResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()->label('Nuevo rol')]; }

    protected function getHeaderWidgets(): array { return []; }

    public function getSubheading(): ?string
    {
        $alertas = \App\Services\AlertasNomina::alertas();
        if (empty($alertas)) return null;
        return collect($alertas)->pluck('texto')->implode("  •  ");
    }
}
