<?php

namespace App\Filament\Resources\ProductoResource\Pages;

use App\Filament\Resources\ProductoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductos extends ListRecords
{
    protected static string $resource = ProductoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('exportar')->label('Exportar Excel')->icon('heroicon-o-arrow-down-tray')->color('gray')
                ->action(function () {
                    $cols = ['nombre'=>'Nombre','sku'=>'SKU','precio'=>'PVP','iva_rate'=>'IVA','activo'=>'Activo'];
                    $regs = \App\Models\Producto::query()->get();
                    $hoja = \App\Services\ExportadorExcel::deRegistros($regs, $cols);
                    $archivo = 'productos-' . now()->format('Ymd-His') . '.xlsx';
                    $path = \App\Services\ExportadorExcel::generar(['productos' => $hoja], $archivo);
                    \App\Models\Bitacora::registrar('exporto', 'Excel', null, $archivo);
                    return response()->download($path, $archivo)->deleteFileAfterSend(true);
                }),
Actions\CreateAction::make()];
    }
}
