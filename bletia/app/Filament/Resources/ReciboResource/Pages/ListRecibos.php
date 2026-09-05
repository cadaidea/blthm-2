<?php
namespace App\Filament\Resources\ReciboResource\Pages;

use App\Filament\Resources\ReciboResource;
use App\Models\Recibo;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRecibos extends ListRecords
{
    protected static string $resource = ReciboResource::class;

    public function getModel(): string
    {
        return Recibo::class;
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('exportar')->label('Exportar Excel')->icon('heroicon-o-arrow-down-tray')->color('gray')
                ->action(function () {
                    $cols = ['folio'=>'Folio','pedido_id'=>'Pedido','tipo'=>'Tipo','monto'=>'Monto','metodo'=>'Metodo','validado'=>'Validado','created_at'=>'Fecha'];
                    $regs = \App\Models\Recibo::query()->get();
                    $hoja = \App\Services\ExportadorExcel::deRegistros($regs, $cols);
                    $archivo = 'recibos-' . now()->format('Ymd-His') . '.xlsx';
                    $path = \App\Services\ExportadorExcel::generar(['recibos' => $hoja], $archivo);
                    \App\Models\Bitacora::registrar('exporto', 'Excel', null, $archivo);
                    return response()->download($path, $archivo)->deleteFileAfterSend(true);
                }),
Actions\CreateAction::make()->label('Nuevo recibo')];
    }
}
