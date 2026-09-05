<?php
namespace App\Filament\Resources\SolicitudMaterialResource\Pages;
use App\Filament\Resources\SolicitudMaterialResource;
use Filament\Resources\Pages\ListRecords;
class ListSolicitudMaterial extends ListRecords
{
    protected static string $resource = SolicitudMaterialResource::class;
    public function getModel(): string { return \App\Models\MovimientoMaterial::class; }
}
