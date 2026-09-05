<?php

namespace App\Filament\Resources\ReciboResource\Pages;

use App\Filament\Resources\ReciboResource;
use App\Models\Recibo;
use App\Models\PedidoEspecial;
use App\Support\Acl;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class ViewRecibo extends Page
{
    protected static string $resource = ReciboResource::class;
    protected string $view = 'filament.recibos.view';

    public $record;

    public function mount($record): void
    {
        $this->record = Recibo::with(['cliente', 'pedido'])->findOrFail($record);
    }

    public function getTitle(): string
    {
        return 'Recibo ' . ($this->record->folio ?: ('#' . $this->record->id));
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('validar')->label('Validar pago')->icon('heroicon-o-check-badge')->color('success')
                ->visible(fn () => Acl::puedeValidarPago() && ! $this->record->validado)
                ->requiresConfirmation()
                ->modalHeading('Validar pago')
                ->modalDescription(fn () => 'Confirmar el pago de $' . number_format((float) $this->record->monto, 2) . ' por ' . ucfirst((string) $this->record->metodo) . '. Revisa los comprobantes antes de validar.')
                ->action(function () {
                    $esVentaDirecta = empty($this->record->pedido_id) && ! empty($this->record->venta_id);
                    if ($esVentaDirecta) {
                        // venta directa de stock: validar sin depender de un pedido
                        $this->record->update(['validado' => true, 'validado_por' => auth()->id(), 'validado_at' => now()]);
                        $venta = \App\Models\Venta::find($this->record->venta_id);
                        if ($venta) {
                            try {
                                $saldo = \App\Services\RecibosErp::saldoVenta($venta);
                                $monto = number_format((float) $this->record->monto, 2);
                                $metodo = ucfirst((string) ($this->record->metodo ?? '—'));
                                $folio = $venta->numero_comprobante ?: $venta->folio;
                                $titulo = $saldo <= 0 ? 'Pago confirmado · ' . $folio : 'Abono confirmado · ' . $folio;
                                $cuerpo = $saldo <= 0
                                    ? '<p>Confirmamos tu pago de <strong>$' . $monto . '</strong> por <strong>' . $metodo . '</strong>.</p><p>Tu comprobante <strong>' . $folio . '</strong> queda <strong>pagado en su totalidad</strong>.</p>'
                                    : '<p>Confirmamos tu pago de <strong>$' . $monto . '</strong> por <strong>' . $metodo . '</strong>.</p><p>Saldo pendiente: <strong>$' . number_format($saldo, 2) . '</strong>.</p>';
                                $html = \App\Support\CorreoBrand::wrap($titulo, $cuerpo);
                                $cliente = $venta->cliente;
                                if ($cliente && $cliente->email) {
                                    \Illuminate\Support\Facades\Mail::to($cliente->email)->send(new \App\Mail\DocumentoPedido($titulo, $html, []));
                                }
                            } catch (\Throwable $e) { report($e); }
                        }
                    } else {
                        $ped = PedidoEspecial::find($this->record->pedido_id);
                        if (! $ped) { Notification::make()->danger()->title('Pedido no encontrado')->send(); return; }
                        $this->record->update(['validado' => true, 'validado_por' => auth()->id(), 'validado_at' => now()]);
                        \App\Services\RecibosErp::validar($this->record->fresh(), $ped);
                    }
                    if (class_exists(\App\Models\Bitacora::class)) {
                        \App\Models\Bitacora::registrar('validó pago', 'Recibo', $this->record->id, '$' . number_format((float) $this->record->monto, 2) . ' · ' . $this->record->metodo);
                    }
                    Notification::make()->success()->title('Pago validado')->send();
                    $this->record->refresh();
                }),

            // ===== NOVEDADES DEL CHEQUE (solo si el recibo es cheque) =====
            Actions\Action::make('chequeCobrado')->label('Marcar cobrado')->icon('heroicon-o-check-circle')->color('success')
                ->visible(fn () => $this->esCheque() && Acl::puedeValidarPago() && $this->record->cheque_estado !== 'cobrado' && ! in_array($this->record->cheque_estado, ['anulado','rechazado'], true))
                ->requiresConfirmation()
                ->modalHeading('Confirmar cobro del cheque')
                ->modalDescription(fn () => 'Cheque N° ' . ($this->record->cheque_numero ?: '—') . ' por $' . number_format((float) $this->record->monto, 2) . '. Marcar como cobrado/depositado.')
                ->action(function () {
                    \App\Services\ChequeTesoreria::cobrar($this->record);
                    Notification::make()->success()->title('Cheque cobrado')->send();
                    $this->record->refresh();
                }),

            Actions\Action::make('chequeRechazado')->label('Cheque rechazado')->icon('heroicon-o-x-circle')->color('danger')
                ->visible(fn () => $this->esCheque() && Acl::puedeValidarPago() && ! in_array($this->record->cheque_estado, ['anulado','rechazado'], true))
                ->requiresConfirmation()
                ->modalHeading('Cheque rechazado (sin fondos)')
                ->modalDescription('El cheque rebotó. El pago dejará de contar y el saldo del pedido volverá a subir.')
                ->form([\Filament\Forms\Components\Textarea::make('motivo')->label('Motivo')->rows(2)->placeholder('Sin fondos, firma no coincide, etc.')])
                ->action(function (array $data) {
                    \App\Services\ChequeTesoreria::rechazar($this->record, $data['motivo'] ?? null);
                    Notification::make()->warning()->title('Cheque marcado como rechazado')->body('El saldo del pedido se actualizó.')->send();
                    $this->record->refresh();
                }),

            Actions\Action::make('chequeCambio')->label('Cambiar cheque')->icon('heroicon-o-arrow-path')->color('warning')
                ->visible(fn () => $this->esCheque() && Acl::puedeValidarPago() && ! in_array($this->record->cheque_estado, ['anulado','rechazado'], true))
                ->modalHeading('Cambiar por un cheque nuevo')
                ->modalDescription('Anula este cheque y registra el nuevo. El saldo se mantiene si cubre lo mismo.')
                ->form([
                    \Filament\Forms\Components\TextInput::make('cheque_numero')->label('N° del nuevo cheque')->required(),
                    \Filament\Forms\Components\TextInput::make('cheque_banco')->label('Banco')->default(fn () => $this->record->cheque_banco),
                    \Filament\Forms\Components\TextInput::make('cheque_girador')->label('Girador')->default(fn () => $this->record->cheque_girador),
                    \Filament\Forms\Components\DatePicker::make('cheque_fecha_cobro')->label('Fecha de cobro')->required(),
                    \Filament\Forms\Components\TextInput::make('monto')->label('Monto')->numeric()->default(fn () => $this->record->monto)->required(),
                ])
                ->action(function (array $data) {
                    $nuevo = \App\Services\ChequeTesoreria::cambiar($this->record, $data);
                    Notification::make()->success()->title('Cheque cambiado')->body('Nuevo cheque N° ' . ($nuevo->cheque_numero ?: '—') . ' registrado.')->send();
                    $this->record->refresh();
                }),

            Actions\Action::make('chequeAnular')->label('Anular cheque')->icon('heroicon-o-no-symbol')->color('danger')
                ->visible(fn () => $this->esCheque() && Acl::puedeResolverPago() && ! in_array($this->record->cheque_estado, ['anulado','rechazado'], true))
                ->requiresConfirmation()
                ->modalHeading('Anular cheque')
                ->modalDescription('El cheque se anula (no se elimina). Dejará de contar como pago y el saldo subirá.')
                ->form([\Filament\Forms\Components\Textarea::make('motivo')->label('Motivo de anulación')->required()->rows(2)])
                ->action(function (array $data) {
                    \App\Services\ChequeTesoreria::anular($this->record, $data['motivo'] ?? null);
                    Notification::make()->warning()->title('Cheque anulado')->body('El saldo del pedido se actualizó.')->send();
                    $this->record->refresh();
                }),
        ];
    }

    protected function esCheque(): bool
    {
        return strtolower((string) $this->record->metodo) === 'cheque';
    }

    protected function getViewData(): array
    {
        $comps = $this->record->comprobantes
            ? (is_array($this->record->comprobantes) ? $this->record->comprobantes : json_decode($this->record->comprobantes, true))
            : [];
        return ['r' => $this->record, 'comps' => $comps ?: []];
    }
}
