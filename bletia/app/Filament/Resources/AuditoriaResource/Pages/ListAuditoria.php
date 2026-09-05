<?php
namespace App\Filament\Resources\AuditoriaResource\Pages;
use App\Models\Bitacora;
use App\Filament\Resources\AuditoriaResource;
use Filament\Resources\Pages\ListRecords;
class ListAuditoria extends ListRecords
{
    protected static string $resource = AuditoriaResource::class;
    public function getModel(): string { return Bitacora::class; }
}
