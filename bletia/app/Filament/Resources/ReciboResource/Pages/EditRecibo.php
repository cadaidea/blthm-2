<?php
namespace App\Filament\Resources\ReciboResource\Pages;

use App\Filament\Resources\ReciboResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRecibo extends EditRecord
{
    protected static string $resource = ReciboResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('validar')->label('Validar pago')->icon('heroicon-o-check-badge')->color('success')
                ->visible(fn () => \App\Support\Acl::puedeValidarPago() && ! $this->record->validado)
                ->requiresConfirmation()
                ->action(function () {
                    $ped = \App\Models\PedidoEspecial::find($this->record->pedido_id);
                    if (! $ped) return;
                    $this->record->update(['validado' => true, 'validado_por' => auth()->id(), 'validado_at' => now()]);
                    \App\Services\RecibosErp::validar($this->record->fresh(), $ped);
                    \App\Models\Bitacora::registrar('validó pago', 'Recibo', $this->record->id, null);
                    \Filament\Notifications\Notification::make()->success()->title('Pago validado')->send();
                    $this->record->refresh();
                }),Actions\DeleteAction::make()];
    }
}
