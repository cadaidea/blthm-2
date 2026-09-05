<?php
namespace App\Filament\Resources\PedidoEspecialResource\Pages;

use App\Filament\Resources\PedidoEspecialResource;
use App\Models\PedidoEspecial;
use App\Models\Proveedor;
use App\Services\FlujoErp;
use App\Services\EstadoPedidoErp;
use App\Support\Acl;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditPedidoEspecial extends EditRecord
{
    protected static string $resource = PedidoEspecialResource::class;

    /** estados en los que el pedido ya está en fabricación (solo ver + cambiar fecha / reasignar / anular). */
    protected function enFabricacion(): bool
    {
        return in_array($this->record->estado_erp, ['enviado_proveedor', 'en_fabricacion', 'en_produccion', 'listo_proveedor'], true);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('ingresarCobro')->label('Ingresar cobro')->icon('heroicon-o-banknotes')->color('success')
                ->visible(fn () => \App\Support\Acl::puedeRegistrarPago() && ! in_array($this->record->estado_erp, ['anulado','cancelado'], true))
                ->url(fn () => \App\Filament\Resources\ReciboResource::getUrl('create') . '?pedido_id=' . $this->record->id),
            Actions\Action::make('solicitarAnticipo')->label('Solicitar anticipo')->icon('heroicon-o-bell-alert')->color('warning')
                ->visible(fn () => \App\Support\Acl::puedeAprobar() && \App\Services\RecibosErp::pagado($this->record) <= 0)
                ->requiresConfirmation()->modalHeading('Solicitar anticipo')->modalDescription('Se avisará al responsable de la venta (dashboard y correo).')
                ->action(function () {
                    $p = $this->record;
                    \Illuminate\Support\Facades\DB::table('pedidos')->where('id', $p->id)->update(['anticipo_solicitado_at' => now()]);
                    \App\Services\Traza::registrar($p, 'anticipo_solicitado');
                    $vend = $p->vendedor_id ? \Illuminate\Support\Facades\DB::table('users')->where('id', $p->vendedor_id)->first() : null;
                    $folio = $p->folio ?: $p->id;
                    if ($vend) {
                        try { \Filament\Notifications\Notification::make()->warning()->title('Solicitan anticipo')->body('Pedido ' . $folio . ': registra el cobro del anticipo.')->sendToDatabase(\App\Models\User::find($vend->id)); } catch (\Throwable $e) { report($e); }
                        if (! empty($vend->email)) {
                            $cuerpo = '<p>Se solicita <strong>anticipo</strong> para el pedido <strong>' . $folio . '</strong> antes de enviarlo a fabricación.</p><p>Ingresa el cobro desde el pedido con el botón <strong>Ingresar cobro</strong>.</p>';
                            $html = \App\Support\CorreoBrand::wrap('Solicitud de anticipo · ' . $folio, $cuerpo, ['cta' => ['text' => 'Ver pedido', 'url' => \App\Filament\Resources\PedidoEspecialResource::getUrl('edit', ['record' => $p->id])]]);
                            try { \Illuminate\Support\Facades\Mail::to($vend->email)->send(new \App\Mail\DocumentoPedido('Solicitud de anticipo · ' . $folio, $html, [])); } catch (\Throwable $e) { report($e); }
                        }
                    }
                    \Filament\Notifications\Notification::make()->success()->title('Anticipo solicitado')->body('Se notificó al responsable de la venta.')->send();
                }),
            // APROBAR (solo en por_aprobar)
            Actions\Action::make('aprobar')
                ->label('Aprobar y enviar a fabricación')
                ->icon('heroicon-o-check-circle')->color('success')
                ->visible(fn () => Acl::puedeAprobar() && $this->record->estado_erp === 'por_aprobar')
                ->modalHeading('Aprobar pedido')
                ->form([
                    Forms\Components\Select::make('destino_fab')->label('¿Quién fabrica?')->required()->live()
                        ->options(['proveedor' => 'Proveedor externo', 'interno' => 'Producción interna (taller)'])
                        ->default('proveedor'),
                    Forms\Components\Select::make('proveedor_id')->label('Proveedor')
                        ->options(fn () => Proveedor::where('activo', true)->pluck('nombre', 'id'))->searchable()
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('destino_fab') === 'proveedor')
                        ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('destino_fab') === 'proveedor')
                        ->helperText('Se asignará a todos los ítems del pedido.'),
                    // fecha comprometida pre-cargada con la solicitada por el vendedor
                    Forms\Components\DatePicker::make('fecha_comprometida')->label('Fecha comprometida de entrega')
                        ->default(fn () => $this->record->fecha_comprometida ?: $this->record->fecha_solicitada)
                        ->required()->minDate(now())
                        ->helperText('Viene de la fecha que pidió el cliente. Puedes ajustarla abajo si hay cambios.'),
                    // nueva fecha opcional (si hay cambios respecto a la del cliente)
                    Forms\Components\DatePicker::make('nueva_fecha')->label('Nueva fecha (opcional, si hay cambios)')
                        ->minDate(now())
                        ->helperText('Si la pones, será la fecha de entrega y se notifica a las partes.'),
                ])
                ->action(function (array $data) {
                    $record = $this->record;
                    $destino = $data['destino_fab'] ?? 'proveedor';
                    if ($destino === 'proveedor' && ! empty($data['proveedor_id'])) {
                        DB::table('pedido_items')->where('pedido_id', $record->id)->update(['proveedor_id' => $data['proveedor_id']]);
                    }
                    $fecha = ! empty($data['nueva_fecha']) ? $data['nueva_fecha'] : $data['fecha_comprometida'];
                    DB::table('pedidos')->where('id', $record->id)->update(['fecha_comprometida' => $fecha]);
                    $res = FlujoErp::aprobar($record->fresh(), $destino);
                    if ($res['ok']) {
                        // si pusieron nueva fecha distinta a la solicitada, notificar cambio
                        if (! empty($data['nueva_fecha']) && $data['nueva_fecha'] !== ($record->fecha_solicitada ?? null)) {
                            FlujoErp::cambiarFecha($record->fresh(), $data['nueva_fecha'], 'Ajuste al aprobar');
                        }
                        Notification::make()->success()->title($res['msg'] ?? 'Aprobado')->send();
                        $this->redirect(PedidoEspecialResource::getUrl('index'));
                    } else {
                        Notification::make()->danger()->title($res['msg'] ?? 'No se pudo aprobar')->send();
                    }
                }),

            // CAMBIAR FECHA (en proceso)
            Actions\Action::make('cambiarFecha')
                ->label('Cambiar fecha')->icon('heroicon-o-calendar')->color('warning')
                ->visible(fn () => Acl::puedeAprobar() && in_array($this->record->estado_erp, ['aprobado', 'enviado_proveedor', 'en_fabricacion', 'en_produccion', 'listo_proveedor'], true))
                ->form([
                    Forms\Components\DatePicker::make('fecha_comprometida')->label('Nueva fecha comprometida')->required()->minDate(now())
                        ->default(fn () => $this->record->fecha_comprometida ?: $this->record->fecha_solicitada),
                    Forms\Components\Textarea::make('motivo')->label('Motivo del cambio')->rows(2),
                ])
                ->action(function (array $data) {
                    FlujoErp::cambiarFecha($this->record, $data['fecha_comprometida'], $data['motivo'] ?? null);
                    Notification::make()->success()->title('Fecha actualizada')->body('Se notificó a vendedor y cliente.')->send();
                }),

            // REASIGNAR PROVEEDOR (si el actual no cumple) — solo cuando está con proveedor externo en proceso
            Actions\Action::make('reasignar')
                ->label('Reasignar proveedor')->icon('heroicon-o-arrow-path-rounded-square')->color('warning')
                ->visible(fn () => Acl::puedeAprobar() && $this->record->destino_fab === 'proveedor' && $this->enFabricacion())
                ->modalHeading('Reasignar proveedor')
                ->form([
                    Forms\Components\Select::make('proveedor_id')->label('Nuevo proveedor')->required()
                        ->options(fn () => Proveedor::where('activo', true)->pluck('nombre', 'id'))->searchable(),
                ])
                ->action(function (array $data) {
                    $res = FlujoErp::reasignarProveedor($this->record, (int) $data['proveedor_id']);
                    Notification::make()->success()->title($res['msg'] ?? 'Proveedor reasignado')->send();
                    $this->redirect(PedidoEspecialResource::getUrl('index'));
                }),

            // ANULAR (admin/operaciones) — disponible casi siempre salvo ya entregado/anulado
            Actions\Action::make('anular')
                ->label('Anular pedido')->icon('heroicon-o-x-circle')->color('danger')
                ->visible(fn () => Acl::puedeAprobar() && ! in_array($this->record->estado_erp, ['anulado', 'cancelado', 'entregado'], true))
                ->modalHeading('Anular pedido')
                ->requiresConfirmation()
                ->form([
                    Forms\Components\Textarea::make('motivo')->label('Motivo de anulación')->required()->rows(2),
                ])
                ->action(function (array $data) {
                    EstadoPedidoErp::anular($this->record, $data['motivo'] ?? null, true);
                    Notification::make()->success()->title('Pedido anulado')->body('Se notificó a las partes.')->send();
                    $this->redirect(PedidoEspecialResource::getUrl('index'));
                }),
        ];
    }
}
