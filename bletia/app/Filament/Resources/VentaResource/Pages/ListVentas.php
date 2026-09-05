<?php

namespace App\Filament\Resources\VentaResource\Pages;

use App\Filament\Resources\VentaResource;
use App\Models\Pedido;
use App\Models\Venta;
use App\Services\Facturacion;
use App\Support\Acl;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListVentas extends ListRecords
{
    protected static string $resource = VentaResource::class;

    protected function getHeaderActions(): array
    {
        $puede = Acl::esAdmin() || Acl::esOperaciones() || Acl::rol() === 'contabilidad';

        return [
            Action::make('facturar')
                ->label('Facturar pedido')
                ->icon('heroicon-o-receipt-percent')
                ->visible($puede)
                ->form([
                    Select::make('pedido_id')
                        ->label('Pedido (sin factura)')
                        ->required()
                        ->searchable()
                        ->options(function () {
                            $ya = Venta::pluck('pedido_id')->filter()->all();
                            return Pedido::query()
                                ->whereNotIn('id', $ya)
                                ->whereNotIn('estado_erp', ['borrador', 'anulado', 'cancelado'])
                                ->orderByDesc('id')
                                ->get()
                                ->mapWithKeys(fn ($p) => [$p->id => ($p->folio ?: '#'.$p->id).' · '.($p->cliente?->nombre ?? 's/cliente')])
                                ->all();
                        }),
                    TextInput::make('nro_factura')->label('N.º de factura')->required()->maxLength(50),
                    DatePicker::make('fecha')->label('Fecha emisión')->default(now()),
                ])
                ->action(function (array $data) {
                    $pedido = Pedido::find($data['pedido_id']);
                    if (! $pedido) return;
                    $venta = Facturacion::registrar($pedido, $data['nro_factura'], $data['fecha'] ?? null);
                    Notification::make()
                        ->title('Venta registrada')
                        ->body("Factura {$venta->nro_factura} · total $".number_format($venta->total, 2))
                        ->success()->send();
                }),
        ];
    }
}
