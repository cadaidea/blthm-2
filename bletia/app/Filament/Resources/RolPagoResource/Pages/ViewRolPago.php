<?php
namespace App\Filament\Resources\RolPagoResource\Pages;
use App\Filament\Resources\RolPagoResource;
use Filament\Resources\Pages\ViewRecord;
class ViewRolPago extends ViewRecord
{
    protected static string $resource = RolPagoResource::class;
    protected string $view = 'filament.pages.ver-rol-pago';
}
