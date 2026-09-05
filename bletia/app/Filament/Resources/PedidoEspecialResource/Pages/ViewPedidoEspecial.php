<?php
namespace App\Filament\Resources\PedidoEspecialResource\Pages;

use App\Filament\Resources\PedidoEspecialResource;
use App\Models\PedidoEspecial;
use App\Models\Proveedor;
use App\Services\EstadoPedidoErp;
use App\Services\FlujoErp;
use App\Services\RecibosErp;
use App\Support\Acl;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;

class ViewPedidoEspecial extends Page
{
    protected static string $resource = PedidoEspecialResource::class;
    protected string $view = 'filament.pedido.view';

    public $record;

    public function mount($record): void
    {
        $this->record = PedidoEspecial::with(['items', 'cliente', 'historial'])->findOrFail($record);
    }

    public function getTitle(): string
    {
        return 'Pedido ' . ($this->record->folio ?: ('#' . $this->record->id));
    }

    protected function enFabricacion(): bool
    {
        return in_array($this->record->estado_erp, ['enviado_proveedor', 'en_fabricacion', 'en_produccion', 'listo_proveedor'], true);
    }

    protected function getHeaderActions(): array
    {
        return [
            // FACTURAR (SRI)
            Actions\Action::make('facturarSri')
                ->label('Emitir comprobante')->icon('heroicon-o-document-text')->color('primary')
                ->visible(fn () => \App\Support\Acl::puedeRegistrarPago()
                    && ! $this->record->nro_factura
                    && ! in_array($this->record->estado_erp, ['anulado','cancelado'], true))
                ->modalHeading('Emitir comprobante')
                ->modalDescription(fn () => $this->descripcionPagos())
                ->modalSubmitActionLabel('Emitir')
                ->form([
                    \Filament\Forms\Components\Radio::make('tipo_comprobante')->label('Tipo de comprobante')->required()
                        ->default('factura')
                        ->options([
                            'factura' => 'Factura (electrónica, se envía al SRI)',
                            'nota_venta' => 'Nota de venta (documento interno)',
                        ]),
                    \Filament\Forms\Components\Textarea::make('info_adicional')->label('Información adicional (opcional)')
                        ->placeholder('Ej: incluye instalación, garantía 1 año...')->rows(2),
                ])
                ->action(function (array $data) {
                    $pagos = \App\Services\Sri\FormasPago::dePedido($this->record->fresh());
                    $formaUnica = $pagos[0]['forma'] ?? '01';
                    $r = \App\Services\Sri\EmitirComprobante::emitir(
                        $this->record->fresh(),
                        $data['tipo_comprobante'] ?? 'factura',
                        $formaUnica,
                        $pagos ?: null,
                        $data['info_adicional'] ?? null
                    );
                    if ($r['ok'] ?? false) {
                        $titulo = ($data['tipo_comprobante'] ?? '') === 'nota_venta'
                            ? 'Nota de venta ' . ($r['numero'] ?? '') . ' emitida'
                            : 'Factura ' . ($r['numero'] ?? '') . ' autorizada';
                        \Filament\Notifications\Notification::make()->success()
                            ->title($titulo)->body($r['ride'] ?? 'Comprobante emitido.')->persistent()->send();
                    } else {
                        \Filament\Notifications\Notification::make()->danger()
                            ->title('No se pudo emitir')->body($r['msg'] ?? 'Error desconocido.')->persistent()->send();
                    }
                    $this->record->refresh();
                }),
            Actions\Action::make('ingresarCobro')->label('Ingresar cobro')->icon('heroicon-o-banknotes')->color('success')
                ->visible(fn () => \App\Support\Acl::puedeRegistrarPago() && ! in_array($this->record->estado_erp, ['anulado','cancelado'], true) && \App\Services\RecibosErp::saldo($this->record) > 0)
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
            // APROBAR
            Actions\Action::make('aprobar')
                ->label('Aprobar y enviar a fabricación')->icon('heroicon-o-check-circle')->color('success')
                ->visible(fn () => Acl::puedeAprobar() && $this->record->estado_erp === 'por_aprobar')
                ->modalHeading('Aprobar pedido')
                ->form([
                    Forms\Components\Select::make('destino_fab')->label('¿Quién fabrica?')->required()->live()
                        ->options(['proveedor' => 'Proveedor externo', 'interno' => 'Producción interna (taller)'])->default('proveedor'),
                    Forms\Components\Select::make('proveedor_id')->label('Proveedor')
                        ->options(fn () => Proveedor::where('activo', true)->pluck('nombre', 'id'))->searchable()
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('destino_fab') === 'proveedor')
                        ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('destino_fab') === 'proveedor'),
                    Forms\Components\DatePicker::make('fecha_comprometida')->label('Fecha comprometida de entrega')
                        ->default(fn () => $this->record->fecha_comprometida ?: $this->record->fecha_solicitada)->required()->minDate(now()),
                    Forms\Components\DatePicker::make('nueva_fecha')->label('Nueva fecha (opcional, si hay cambios)')->minDate(now())
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
                        if (! empty($data['nueva_fecha'])) FlujoErp::cambiarFecha($record->fresh(), $data['nueva_fecha'], 'Ajuste al aprobar');
                        Notification::make()->success()->title($res['msg'] ?? 'Aprobado')->send();
                        $this->redirect(PedidoEspecialResource::getUrl('index'));
                    } else {
                        Notification::make()->danger()->title($res['msg'] ?? 'No se pudo aprobar')->send();
                    }
                }),

            // CAMBIAR FECHA
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
                    Notification::make()->success()->title('Fecha actualizada')->send();
                    $this->record->refresh();
                }),

            // REASIGNAR PROVEEDOR
            Actions\Action::make('reasignar')
                ->label('Reasignar proveedor')->icon('heroicon-o-arrow-path-rounded-square')->color('warning')
                ->visible(fn () => Acl::puedeAprobar() && $this->record->destino_fab === 'proveedor' && $this->enFabricacion())
                ->form([
                    Forms\Components\Select::make('proveedor_id')->label('Nuevo proveedor')->required()
                        ->options(fn () => Proveedor::where('activo', true)->pluck('nombre', 'id'))->searchable(),
                ])
                ->action(function (array $data) {
                    $res = FlujoErp::reasignarProveedor($this->record, (int) $data['proveedor_id']);
                    Notification::make()->success()->title($res['msg'] ?? 'Reasignado')->send();
                    $this->redirect(PedidoEspecialResource::getUrl('index'));
                }),

            // ANULAR
            Actions\Action::make('anular')
                ->label('Anular pedido')->icon('heroicon-o-x-circle')->color('danger')
                ->visible(fn () => Acl::puedeAprobar() && ! in_array($this->record->estado_erp, ['anulado', 'cancelado', 'entregado'], true))
                ->requiresConfirmation()
                ->form([Forms\Components\Textarea::make('motivo')->label('Motivo de anulación')->required()->rows(2)])
                ->action(function (array $data) {
                    EstadoPedidoErp::anular($this->record, $data['motivo'] ?? null, true);
                    Notification::make()->success()->title('Pedido anulado')->body('Se notificó a las partes.')->send();
                    $this->redirect(PedidoEspecialResource::getUrl('index'));
                }),
        ];
    }

    // VALIDAR PAGO desde el View (acción Livewire llamada por el botón en la vista)
    public function validarPago(int $reciboId): void
    {
        if (! Acl::puedeValidarPago()) {
            Notification::make()->danger()->title('No tienes permiso para validar pagos')->send();
            return;
        }
        $recibo = \App\Models\Recibo::find($reciboId);
        if (! $recibo || $recibo->validado) return;
        $recibo->update(['validado' => true, 'validado_por' => auth()->id(), 'validado_at' => now()]);
        RecibosErp::validar($recibo->fresh(), $this->record->fresh());
        \App\Models\Bitacora::registrar('validó pago', 'Recibo', $recibo->id, null);
        Notification::make()->success()->title('Pago validado')->send();
        $this->record->refresh();
    }

    protected function getViewData(): array
    {
        $p = $this->record;
        $userName = fn ($id) => $id ? DB::table('users')->where('id', $id)->value('name') : null;
        $proveedorNombre = null;
        if ($p->destino_fab === 'proveedor') {
            $provId = DB::table('pedido_items')->where('pedido_id', $p->id)->whereNotNull('proveedor_id')->value('proveedor_id');
            if ($provId) $proveedorNombre = DB::table('proveedores')->where('id', $provId)->value('nombre');
        }
        return [
            'p'          => $p,
            'estados'    => EstadoPedidoErp::ESTADOS,
            'saldo'      => RecibosErp::saldo($p),
            'pagado'     => RecibosErp::pagado($p),
            'vendidoPor' => $userName($p->vendido_por) ?? $userName($p->vendedor_id),
            'aprobadoPor'=> $userName($p->aprobado_por),
            'fabricaEn'  => $p->destino_fab === 'interno' ? 'Producción interna (taller)' : ($proveedorNombre ? 'Proveedor: ' . $proveedorNombre : 'Proveedor externo'),
            'recibos'    => DB::table('recibos')->where('pedido_id', $p->id)->orderBy('id')->get(),
            'puedeValidar' => Acl::puedeValidarPago(),
            'avatars'    => DB::table('users')->pluck('avatar', 'id')->map(fn ($a) => $a ? \Illuminate\Support\Facades\Storage::disk('public')->url($a) : null)->toArray(),
        ];
    }

    public function descripcionPagos(): string
    {
        $recibos = \Illuminate\Support\Facades\DB::table('recibos')
            ->where('pedido_id', $this->record->id)->where('validado', 1)
            ->get(['metodo', 'tipo_tarjeta', 'monto']);

        $labels = [
            'efectivo' => 'Efectivo', 'transferencia' => 'Transferencia',
            'tarjeta' => 'Tarjeta', 'deposito' => 'Depósito', 'cheque' => 'Cheque', 'otro' => 'Otro',
        ];
        $acc = [];
        $pagado = 0.0;
        foreach ($recibos as $r) {
            $key = $labels[strtolower((string) $r->metodo)] ?? ucfirst((string) $r->metodo);
            if (strtolower((string) $r->metodo) === 'tarjeta' && $r->tipo_tarjeta) {
                $key .= ' (' . ucfirst((string) $r->tipo_tarjeta) . ')';
            }
            $acc[$key] = ($acc[$key] ?? 0) + (float) $r->monto;
            $pagado += (float) $r->monto;
        }

        $total = round((float) \Illuminate\Support\Facades\DB::table('pedido_items')->where('pedido_id', $this->record->id)->sum('subtotal'), 2);
        $saldo = round($total - $pagado, 2);

        if (! $acc) {
            return 'Sin cobros registrados. Saldo pendiente: $' . number_format($total, 2) . '. Se usará efectivo por defecto.';
        }

        $partes = [];
        foreach ($acc as $metodo => $monto) $partes[] = $metodo . ' $' . number_format($monto, 2);
        $txt = 'Cobros: ' . implode(' · ', $partes);
        if ($saldo > 0.005) {
            $txt .= '  |  Saldo pendiente: $' . number_format($saldo, 2);
        } else {
            $txt .= '  |  Pagado en su totalidad.';
        }
        return $txt;
    }
}
