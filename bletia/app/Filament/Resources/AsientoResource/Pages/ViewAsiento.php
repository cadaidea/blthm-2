<?php
namespace App\Filament\Resources\AsientoResource\Pages;
use App\Filament\Resources\AsientoResource;
use Filament\Resources\Pages\ViewRecord;
class ViewAsiento extends ViewRecord
{
    protected static string $resource = AsientoResource::class;
    protected string $view = 'filament.pages.ver-asiento';
}
