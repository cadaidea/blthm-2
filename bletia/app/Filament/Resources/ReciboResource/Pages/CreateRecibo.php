<?php
namespace App\Filament\Resources\ReciboResource\Pages;

use App\Filament\Resources\ReciboResource;
use App\Models\Pedido;
use App\Services\RecibosErp;
use Filament\Resources\Pages\CreateRecord;

class CreateRecibo extends CreateRecord
{
    protected static string $resource = ReciboResource::class;
    public bool $notificarCliente = true;

    protected function fillForm(): void
    {
        parent::fillForm();
        $pid = request('pedido_id');
        if ($pid && ($p = \App\Models\Pedido::find($pid))) {
            $this->form->fill(['pedido_id' => $p->id, 'cliente_id' => $p->cliente_id, 'fecha' => now()->toDateString(), 'tipo' => 'abono', 'notificar' => true]);
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->notificarCliente = (bool) ($data['notificar'] ?? true);
        unset($data['notificar']);
        if (! empty($data['pedido_id']) && empty($data['cliente_id'])) {
            $p = Pedido::find($data['pedido_id']);
            if ($p) $data['cliente_id'] = $p->cliente_id;
        }
        if (empty($data['fecha'])) $data['fecha'] = now()->toDateString();

        // EFECTIVO y TARJETA: se validan automáticamente (dinero inmediato, no espera al dueño)
        if (in_array($data['metodo'] ?? null, ['efectivo', 'tarjeta'], true)) {
            $data['validado'] = true;
            $data['validado_at'] = now();
            $data['validado_por'] = auth()->id();
        }
        return $data;
    }

    protected function afterCreate(): void
    {
        $p = Pedido::find($this->record->pedido_id);
        if (! $p) return;

        if (in_array($this->record->metodo, ['efectivo', 'tarjeta'], true)) {
            // Pago confirmado al instante -> notifica cliente + contabilidad
            RecibosErp::validar($this->record, $p);
        } else {
            // Resto: avisa al dueño para que valide (si el usuario dejó el toggle activo)
            if ($this->notificarCliente) {
                RecibosErp::avisarValidacion($this->record, $p);
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
