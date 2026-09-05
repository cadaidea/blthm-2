<?php

namespace App\Filament\Resources\CompraResource\Pages;

use App\Filament\Resources\CompraResource;
use App\Models\Compra;
use App\Support\Acl;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class ViewCompra extends Page
{
    protected static string $resource = CompraResource::class;
    protected string $view = 'filament.compras.view';
    public $record;

    public function mount($record): void
    {
        $this->record = Compra::with(['items', 'pagos', 'proveedor', 'localDestino'])->findOrFail($record);
    }

    public function getTitle(): string
    {
        return 'Compra ' . ($this->record->folio ?: ('#' . $this->record->id));
    }

    protected function getHeaderActions(): array
    {
        return [
            // ===== AVANZAR ESTADO =====
            Actions\Action::make('aProceso')->label('Marcar en proceso')->icon('heroicon-o-cog-6-tooth')->color('warning')
                ->visible(fn () => $this->puedeGestionarEsta() && $this->record->estado === 'creada')
                ->action(fn () => $this->cambiarEstado('en_proceso', 'Marcado en proceso')),

            Actions\Action::make('aListoEnvio')->label('Listo para enviar')->icon('heroicon-o-archive-box')->color('info')
                ->visible(fn () => $this->puedeGestionarEsta() && in_array($this->record->estado, ['creada', 'en_proceso'], true))
                ->action(fn () => $this->cambiarEstado('listo_envio', 'Listo para enviar')),

            // ===== GENERAR DESPACHO (traslado a tu local/bodega, con guía SRI) =====
            Actions\Action::make('generarDespacho')->label('Generar despacho/guía')->icon('heroicon-o-truck')->color('primary')
                ->visible(fn () => Acl::puedeGenerarGuia() // logística mueve el traslado físico, no producción
                    && $this->record->estado === 'listo_envio'
                    && ! \Illuminate\Support\Facades\DB::table('despachos')->where('compra_id', $this->record->id)->exists())
                ->modalHeading('Generar despacho hacia tu local/bodega')
                ->modalDescription('Elige el local destino y el empleado que debe recibir y validar la mercadería.')
                ->fillForm(fn () => ['local_destino_id' => $this->record->local_destino_id])
                ->form([
                    Forms\Components\Select::make('local_destino_id')->label('Local/bodega destino')
                        ->options(fn () => \Illuminate\Support\Facades\DB::table('locales')->pluck('nombre', 'id'))
                        ->required()->searchable(),
                    Forms\Components\Select::make('empleado_receptor_id')->label('Empleado que recibe y valida')
                        ->options(fn () => \App\Models\User::where('activo', true)->pluck('name', 'id'))
                        ->required()->searchable(),
                ])
                ->action(function (array $data) {
                    $c = $this->record;
                    $folio = \App\Services\Folios::next('DES');
                    \Illuminate\Support\Facades\DB::table('despachos')->insert([
                        'compra_id' => $c->id, 'pedido_id' => null,
                        'tipo' => 'abastecimiento',
                        'ruta' => 'transportista', 'estado' => 'programado', 'listo' => true,
                        'folio' => $folio,
                        'local_destino_id' => $data['local_destino_id'],
                        'empleado_receptor_id' => $data['empleado_receptor_id'],
                        'notas' => 'Abastecimiento · ' . ($c->tipo === 'proveedor' ? 'Compra a proveedor' : 'Producción interna') . ' · ' . ($c->folio ?: ''),
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    $this->cambiarEstado('en_transito', null, false);
                    if (class_exists(\App\Models\Bitacora::class)) {
                        \App\Models\Bitacora::registrar('generó despacho de abastecimiento', 'Compra', $c->id, ($c->folio ?: '') . ' · ' . $folio);
                    }
                    $empleado = \App\Models\User::find($data['empleado_receptor_id']);
                    if ($empleado && $empleado->email) {
                        try {
                            \Illuminate\Support\Facades\Mail::to($empleado->email)->send(new \App\Mail\DocumentoPedido(
                                'Despacho asignado · ' . $folio,
                                \App\Support\CorreoBrand::wrap('Despacho pendiente de recibir', '<p>Se te asignó el despacho <strong>' . $folio . '</strong> (abastecimiento). Revisa el módulo Despachos para validar la recepción.</p>'),
                                []
                            ));
                        } catch (\Throwable $e) { report($e); }
                    }
                    Notification::make()->success()->title('Despacho generado')->body('Folio ' . $folio . '. Asignado a ' . ($empleado->name ?? '—') . '.')->send();
                    $this->record->refresh();
                }),

            // ===== MARCAR RECIBIDA (suma stock) — sin despacho, recepción directa en el mismo local =====
            Actions\Action::make('marcarRecibida')->label('Marcar recibida (sumar stock)')->icon('heroicon-o-check-circle')->color('success')
                ->visible(fn () => $this->puedeGestionarEsta()
                    && in_array($this->record->estado, ['creada', 'en_proceso', 'listo_envio'], true))
                ->requiresConfirmation()
                ->modalHeading('Confirmar recepción')
                ->modalDescription('Se sumará el stock de todos los ítems al local/bodega destino. Usa esto si la mercadería ya está físicamente en el local (sin necesidad de despacho/guía).')
                ->action(function () { $this->recibir(); }),

            Actions\Action::make('editar')->label('Editar')->icon('heroicon-o-pencil')->color('gray')
                ->visible(fn () => $this->record->estado === 'creada' && $this->puedeGestionarEsta())
                ->url(fn () => CompraResource::getUrl('edit', ['record' => $this->record])),

            Actions\Action::make('anular')->label('Anular')->icon('heroicon-o-x-circle')->color('danger')
                ->visible(fn () => Acl::puedeGestionarCompraProveedor() && ! in_array($this->record->estado, ['recibida', 'anulada'], true))
                ->requiresConfirmation()
                ->form([Forms\Components\Textarea::make('motivo')->label('Motivo')->required()->rows(2)])
                ->action(function (array $data) {
                    $this->record->update(['estado' => 'anulada', 'notas' => trim(($this->record->notas ?: '') . "\nAnulada: " . $data['motivo'])]);
                    if (class_exists(\App\Models\Bitacora::class)) {
                        \App\Models\Bitacora::registrar('anuló compra', 'Compra', $this->record->id, $this->record->folio ?: '');
                    }
                    Notification::make()->warning()->title('Compra anulada')->send();
                    $this->record->refresh();
                }),

            // ===== REGISTRAR PAGO AL PROVEEDOR =====
            Actions\Action::make('registrarPago')
                ->label(fn () => $this->record->tipo === 'produccion_interna' ? 'Registrar gasto adicional' : 'Registrar pago')
                ->icon('heroicon-o-banknotes')->color('success')
                ->visible(fn () => Acl::puedeGestionarCompraProveedor() && $this->record->estado !== 'anulada' && ($this->record->tipo === 'produccion_interna' || $this->record->saldo() > 0))
                ->modalHeading(fn () => $this->record->tipo === 'produccion_interna' ? 'Registrar gasto adicional (opcional)' : 'Registrar pago al proveedor')
                ->modalDescription(fn () => $this->record->tipo === 'produccion_interna'
                    ? 'El costo real ya se calcula con el material usado. Usa esto solo para un gasto extra puntual (ej. mano de obra externa).'
                    : 'Saldo pendiente: $' . number_format($this->record->saldo(), 2))
                ->form([
                    Forms\Components\TextInput::make('monto')->numeric()->prefix('$')->required()->default(fn () => $this->record->saldo()),
                    Forms\Components\Select::make('metodo')->label('Método')->required()->live()
                        ->options(['efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'deposito' => 'Depósito', 'cheque' => 'Cheque', 'tarjeta' => 'Tarjeta']),
                    Forms\Components\DatePicker::make('fecha')->default(now())->required(),
                    Forms\Components\Select::make('tarjeta_naturaleza')->label('Tipo de tarjeta')
                        ->options(['debito' => 'Débito', 'credito' => 'Crédito'])
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('metodo') === 'tarjeta'),
                    Forms\Components\TextInput::make('nro_comprobante')->label('N° comprobante')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => in_array($get('metodo'), ['transferencia', 'deposito'], true)),
                    Forms\Components\TextInput::make('cheque_girador')->label('Girador')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('metodo') === 'cheque'),
                    Forms\Components\TextInput::make('cheque_numero')->label('N° cheque')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('metodo') === 'cheque'),
                    Forms\Components\TextInput::make('cheque_banco')->label('Banco')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('metodo') === 'cheque'),
                    Forms\Components\DatePicker::make('cheque_fecha_cobro')->label('Fecha de cobro')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('metodo') === 'cheque')
                        ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('metodo') === 'cheque'),
                    Forms\Components\FileUpload::make('comprobantes')->label('Comprobante (foto, opcional)')->image()->multiple()
                        ->directory('compra-pagos')->disk('public')->maxFiles(5),
                    Forms\Components\Textarea::make('nota')->rows(2),
                ])
                ->action(function (array $data) {
                    \App\Models\CompraPago::create(array_merge($data, [
                        'compra_id' => $this->record->id,
                        'creado_por' => auth()->id(),
                        'cheque_estado' => 'pendiente',
                    ]));
                    if (class_exists(\App\Models\Bitacora::class)) {
                        \App\Models\Bitacora::registrar('registró pago a proveedor', 'Compra', $this->record->id, '$' . number_format((float) $data['monto'], 2));
                    }
                    Notification::make()->success()->title('Pago registrado')->send();
                    $this->record->refresh();
                }),
        ];
    }

    protected function puedeGestionarEsta(): bool
    {
        return $this->record->tipo === "produccion_interna"
            ? Acl::puedeGestionarProduccionInterna()
            : Acl::puedeGestionarCompraProveedor();
    }

    protected function cambiarEstado(string $estado, ?string $msg = null, bool $notify = true): void
    {
        $this->record->update(['estado' => $estado]);
        if (class_exists(\App\Models\Bitacora::class)) {
            \App\Models\Bitacora::registrar('cambió estado de compra', 'Compra', $this->record->id, ($this->record->folio ?: '') . ' → ' . $estado);
        }
        if ($notify && $msg) Notification::make()->success()->title($msg)->send();
        $this->record->refresh();
    }

    /** Suma el stock de todos los ítems al local destino (de la combinación exacta si aplica). */
    protected function recibir(): void
    {
        $c = $this->record;
        foreach ($c->items as $it) {
            \App\Models\MovimientoStock::create([
                'producto_id' => $it->producto_id,
                'variante_id' => $it->variante_id,
                'local_id' => $c->local_destino_id,
                'tipo' => 'entrada',
                'cantidad' => (int) $it->cantidad,
                'referencia' => $c->folio ?: ('compra-' . $c->id),
                'nota' => ($c->tipo === 'proveedor' ? 'Compra a proveedor' : 'Producción interna') . ' · ' . ($c->folio ?: ''),
            ]);
        }
        $c->update(['estado' => 'recibida', 'recibida_at' => now()]);
        if (class_exists(\App\Models\Bitacora::class)) {
            \App\Models\Bitacora::registrar('recibió compra/producción', 'Compra', $c->id, ($c->folio ?: '') . ' · stock sumado en ' . (optional($c->localDestino)->nombre ?: '—'));
        }
        Notification::make()->success()->title('Recepción confirmada')->body('Stock sumado al local destino.')->send();
        $this->record->refresh();
    }

    protected function getViewData(): array
    {
        $materiales = $this->record->tipo === 'produccion_interna'
            ? \App\Models\MovimientoMaterial::where('compra_id', $this->record->id)->with('materia')->orderByDesc('id')->get()
            : collect();
        $costoMaterialReal = $materiales->where('estado', 'entregado')->sum(function ($m) {
            return (float) $m->cantidad * (float) optional($m->materia)->costo;
        });
        return ['c' => $this->record, 'materiales' => $materiales, 'costoMaterialReal' => $costoMaterialReal];
    }
}
