<?php
namespace App\Filament\Resources\ClienteResource\Pages;
use App\Filament\Resources\ClienteResource;
use Filament\Resources\Pages\ListRecords;
class ListCliente extends ListRecords { protected static string $resource = ClienteResource::class; 
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('exportar')->label('Exportar Excel')->icon('heroicon-o-arrow-down-tray')->color('gray')
                ->action(function () {
                    $cols = ['nombre'=>'Nombre','cedula_ruc'=>'Cedula/RUC','email'=>'Email','celular'=>'Celular','ciudad'=>'Ciudad','saldo_favor'=>'Saldo a favor'];
                    $regs = \App\Models\Cliente::query()->get();
                    $hoja = \App\Services\ExportadorExcel::deRegistros($regs, $cols);
                    $archivo = 'clientes-' . now()->format('Ymd-His') . '.xlsx';
                    $path = \App\Services\ExportadorExcel::generar(['clientes' => $hoja], $archivo);
                    \App\Models\Bitacora::registrar('exporto', 'Excel', null, $archivo);
                    return response()->download($path, $archivo)->deleteFileAfterSend(true);
                }),
        ];
    }
}
