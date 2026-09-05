<?php
namespace App\Filament\Resources\DespachoResource\Pages;

use App\Filament\Resources\DespachoResource;
use App\Models\Despacho;
use App\Services\PdfErp;
use App\Services\RecibosErp;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ViewDespacho extends Page
{
    protected static string $resource = DespachoResource::class;
    protected string $view = 'filament.despacho.view';

    public $record;

    public function mount($record): void
    {
        $this->record = Despacho::findOrFail($record);
    }

    public function getTitle(): string
    {
        return 'Despacho ' . ($this->record->folio ?: ('#' . $this->record->id));
    }

    protected function pedido()
    {
        return \App\Models\PedidoEspecial::find($this->record->pedido_id);
    }

    protected function saldoPendiente(): bool
    {
        $p = $this->pedido();
        return $p ? RecibosErp::saldo($p) > 0 : false;
    }

    protected function getHeaderActions(): array
    {
        $esRetiro = $this->record->ruta === 'retiro_local';

        return [
            Actions\Action::make('cobro')->label('Solicitar cobro del saldo')->icon('heroicon-o-banknotes')->color('danger')
                ->visible(fn () => $this->saldoPendiente())->requiresConfirmation()
                ->modalDescription('Notifica a vendedor, operaciones y contabilidad para gestionar el cobro.')
                ->action(fn () => \App\Services\CobroSaldo::solicitar($this->pedido())),
            // GENERAR GUÍA DE REMISIÓN (solo domicilio)
            Actions\Action::make('verGuia')->label('Ver guía de remisión')->icon('heroicon-o-arrow-down-tray')->color('success')
                ->visible(function () {
                    return \App\Models\SriComprobante::where('tipo','guia_remision')->where('pedido_id', $this->record->pedido_id)->where('estado','AUTORIZADO')->exists();
                })
                ->action(function () {
                    $comp = \App\Models\SriComprobante::where('tipo','guia_remision')->where('pedido_id', $this->record->pedido_id)->where('estado','AUTORIZADO')->latest()->first();
                    if (! $comp) { \Filament\Notifications\Notification::make()->danger()->title('No hay guía')->send(); return; }
                    $path = $comp->pdf_path && is_file($comp->pdf_path) ? $comp->pdf_path : \App\Services\Sri\RideGuiaRemision::generar($comp);
                    $num = $comp->estab.'-'.$comp->pto_emi.'-'.str_pad($comp->secuencial,9,'0',STR_PAD_LEFT);
                    return response()->download($path, 'guia-'.$num.'.pdf');
                }),
            Actions\Action::make('etiquetas')->label('Etiquetas de bultos')->icon('heroicon-o-tag')->color('gray')
                ->visible(fn () => ! $this->record->compra_id && ! $this->record->venta_id)
                ->action(function () {
                    $p = $this->pedido();
                    if (! $p) { \Filament\Notifications\Notification::make()->danger()->title('Pedido no encontrado')->send(); return; }
                    $path = \App\Services\Etiquetas::generar($p);
                    return response()->download($path, 'etiquetas-' . ($p->folio ?: $p->id) . '.pdf');
                }),
            Actions\Action::make('guia')
                ->label('Generar guía de remisión')->icon('heroicon-o-document-text')->color('info')
                ->visible(fn () => ! $this->record->compra_id && ! $this->record->venta_id && \App\Support\Acl::puedeGenerarGuia() && ! $esRetiro && $this->record->estado !== 'entregado' && ! $this->saldoPendiente())
                ->modalHeading('Guía de remisión electrónica (SRI)')
                ->modalWidth('2xl')
                ->modalSubmitActionLabel('Emitir guía al SRI')
                ->fillForm(function () {
                    $p = $this->pedido();
                    $d = $this->record;
                    $cli = ($p && $p->cliente_id) ? \Illuminate\Support\Facades\DB::table('clientes')->where('id', $p->cliente_id)->first() : null;
                    $trans = $d->transportista_id ? \Illuminate\Support\Facades\DB::table('transportistas')->where('id', $d->transportista_id)->first() : null;
                    return [
                        'transportista_id' => $d->transportista_id,
                        'trans_razon'   => $trans->empresa ?? $trans->nombre ?? $d->conductor_nombre ?? '',
                        'trans_ruc'     => $trans->identificacion ?? '',
                        'trans_placa'   => $d->placa ?? '',
                        'chofer_nombre' => $d->conductor_nombre ?? '',
                        'chofer_cedula' => $d->conductor_nui ?? '',
                        'fecha_ini'     => $d->fecha_programada ?: now()->toDateString(),
                        'fecha_fin'     => $d->fecha_programada ?: now()->addDay()->toDateString(),
                        'dest_razon'    => $cli->nombre ?? '',
                        'dest_id'       => $cli->cedula_ruc ?? ($cli->identificacion ?? '9999999999999'),
                        'dest_dir'      => $cli->direccion ?? '',
                        'motivo'        => 'Entrega de mercadería vendida',
                    ];
                })
                ->form([
                    \Filament\Schemas\Components\Section::make('Transportista')->columns(2)->schema([
                        \Filament\Forms\Components\Select::make('transportista_id')->label('Empresa de transporte')->columnSpanFull()
                            ->options(fn () => \Illuminate\Support\Facades\DB::table('transportistas')->where('activo', true)->get()
                                ->mapWithKeys(fn ($t) => [$t->id => ($t->empresa ?: $t->nombre) . ($t->identificacion ? ' · ' . $t->identificacion : '')])->all())
                            ->searchable()->live()
                            ->afterStateUpdated(function ($state, \Filament\Schemas\Components\Utilities\Set $set) {
                                if (! $state) return;
                                $t = \Illuminate\Support\Facades\DB::table('transportistas')->where('id', $state)->first();
                                if ($t) { $set('trans_razon', $t->empresa ?: $t->nombre); $set('trans_ruc', $t->identificacion ?? ''); }
                            })
                            ->helperText('Elige de tu lista o escribe los datos abajo.'),
                        \Filament\Forms\Components\TextInput::make('trans_razon')->label('Razón social transportista')->required(),
                        \Filament\Forms\Components\TextInput::make('trans_ruc')->label('RUC / Cédula transportista')->required(),
                        \Filament\Forms\Components\TextInput::make('chofer_nombre')->label('Chofer (nombre)'),
                        \Filament\Forms\Components\TextInput::make('chofer_cedula')->label('Chofer (cédula)'),
                        \Filament\Forms\Components\TextInput::make('trans_placa')->label('Placa del vehículo'),
                    ]),
                    \Filament\Schemas\Components\Section::make('Fechas de traslado')->columns(2)->schema([
                        \Filament\Forms\Components\DatePicker::make('fecha_ini')->label('Fecha de inicio (carga)')->required(),
                        \Filament\Forms\Components\DatePicker::make('fecha_fin')->label('Fecha fin de traslado')->required(),
                    ]),
                    \Filament\Schemas\Components\Section::make('Destinatario')->columns(2)->schema([
                        \Filament\Forms\Components\TextInput::make('dest_razon')->label('Destinatario')->required(),
                        \Filament\Forms\Components\TextInput::make('dest_id')->label('Identificación')->required(),
                        \Filament\Forms\Components\TextInput::make('dest_dir')->label('Dirección de entrega')->columnSpanFull(),
                        \Filament\Forms\Components\TextInput::make('motivo')->label('Motivo del traslado')->columnSpanFull(),
                    ]),
                ])
                ->action(function (array $data) {
                    $p = $this->pedido();
                    if (! $p) { \Filament\Notifications\Notification::make()->danger()->title('Pedido no encontrado')->send(); return; }

                    // items del pedido
                    $items = \Illuminate\Support\Facades\DB::table('pedido_items')->where('pedido_id', $p->id)->get()
                        ->map(fn ($it) => ['codigo' => 'ITEM' . $it->id, 'descripcion' => $it->nombre, 'cantidad' => $it->cantidad])->all();

                    // doc sustento: la factura del pedido si existe
                    $docSustento = [];
                    $venta = \App\Models\Venta::where('pedido_id', $p->id)->where('tipo_comprobante', 'factura')->first();
                    if ($venta && $venta->sri_comprobante_id) {
                        $fc = \App\Models\SriComprobante::find($venta->sri_comprobante_id);
                        if ($fc) {
                            $docSustento = [
                                'cod' => '01',
                                'num' => $fc->estab . '-' . $fc->pto_emi . '-' . str_pad($fc->secuencial, 9, '0', STR_PAD_LEFT),
                                'num_aut' => $fc->numero_autorizacion ?: $fc->clave_acceso,
                                'fecha' => optional($fc->fecha_autorizacion)->format('d/m/Y') ?: date('d/m/Y'),
                            ];
                        }
                    }

                    $g = [
                        'pedido_id' => $p->id, 'cliente_id' => $p->cliente_id, 'despacho_id' => $this->record->id,
                        'dir_partida' => \App\Services\Sri\Emisor::dirEstab(),
                        'transportista' => [
                            'razon' => $data['trans_razon'], 'ruc' => $data['trans_ruc'],
                            'tipo_id' => strlen(preg_replace('/\D/', '', $data['trans_ruc'])) === 13 ? '04' : '05',
                            'placa' => $data['trans_placa'] ?? '',
                        ],
                        'fecha_ini' => \Illuminate\Support\Carbon::parse($data['fecha_ini'])->format('d/m/Y'),
                        'fecha_fin' => \Illuminate\Support\Carbon::parse($data['fecha_fin'])->format('d/m/Y'),
                        'destinatario' => [
                            'razon' => $data['dest_razon'], 'identificacion' => $data['dest_id'],
                            'direccion' => $data['dest_dir'] ?? '', 'motivo' => $data['motivo'] ?? 'Entrega de mercadería',
                            'doc_sustento' => $docSustento,
                        ],
                        'items' => $items,
                        'info_adicional' => [
                            'Chofer' => trim(($data['chofer_nombre'] ?? '') . ' ' . ($data['chofer_cedula'] ?? '')),
                            'Pedido' => $p->folio ?: ('#' . $p->id),
                        ],
                    ];

                    $r = \App\Services\Sri\EmisorGuiaRemision::emitir($g);
                    if ($r['ok'] ?? false) {
                        // guardar conductor/placa en el despacho (para el seguimiento "en ruta")
                        $this->record->update([
                            'transportista_id' => $data['transportista_id'] ?? $this->record->transportista_id,
                            'conductor_nombre' => $data['chofer_nombre'] ?: $data['trans_razon'],
                            'conductor_nui' => $data['chofer_cedula'] ?? null,
                            'placa' => $data['trans_placa'] ?? null,
                        ]);
                        \App\Models\Bitacora::registrar('emitió guía de remisión', 'Despacho', $this->record->id, $r['numero'] ?? '');
                        // RIDE + correo al cliente confirmando despacho
                        try {
                            $comp = \App\Models\SriComprobante::find($r['comprobante_id']);
                            if ($comp) {
                                $pdfGuia = \App\Services\Sri\RideGuiaRemision::generar($comp);
                                $comp->update(['pdf_path' => $pdfGuia]);
                                $cli = $p->cliente_id ? \Illuminate\Support\Facades\DB::table('clientes')->where('id', $p->cliente_id)->first() : null;
                                if ($cli && $cli->email && filter_var($cli->email, FILTER_VALIDATE_EMAIL)) {
                                    $folio = $p->folio ?: ('#'.$p->id);
                                    $cuerpo = '<p>Tu pedido <strong>'.$folio.'</strong> ha sido despachado y va en camino.</p>'
                                        . '<p>Adjuntamos la guía de remisión <strong>'.($r['numero'] ?? '').'</strong> con los datos del transporte.</p>';
                                    $html = \App\Support\CorreoBrand::wrap('Tu pedido va en camino · '.$folio, $cuerpo);
                                    try { \Illuminate\Support\Facades\Mail::to($cli->email)->send(new \App\Mail\DocumentoPedido('Pedido despachado · '.$folio, $html, [$pdfGuia])); } catch (\Throwable $e) { report($e); }
                                }
                            }
                        } catch (\Throwable $e) { report($e); }
                        \Filament\Notifications\Notification::make()->success()->title('Guía de remisión autorizada')->body('N° ' . ($r['numero'] ?? '') . '. La ruta de entrega ha iniciado.')->persistent()->send();
                    } else {
                        \Filament\Notifications\Notification::make()->danger()->title('No se pudo emitir la guía')->body($r['msg'] ?? 'Error desconocido.')->persistent()->send();
                    }
                    $this->record->refresh();
                }),

            // ENTREGAR CON FIRMA (domicilio) / REGISTRAR RETIRO (local) — solo si saldo == 0

            // ===== ABASTECIMIENTO: guía de remisión SRI (traslado interno, mismo RUC) =====
            Actions\Action::make('guiaAbasto')->label('Generar guía de remisión')->icon('heroicon-o-document-text')->color('info')
                ->visible(fn () => $this->record->compra_id && $this->record->estado !== 'entregado' && \App\Support\Acl::puedeGenerarGuia())
                ->modalHeading('Guía de remisión electrónica (SRI) · Traslado interno')
                ->modalWidth('2xl')
                ->modalSubmitActionLabel('Emitir guía al SRI')
                ->fillForm(fn () => [
                    'transportista_id' => $this->record->transportista_id,
                    'trans_razon' => $this->record->conductor_nombre ?? '',
                    'trans_placa' => $this->record->placa ?? '',
                    'chofer_nombre' => $this->record->conductor_nombre ?? '',
                    'chofer_cedula' => $this->record->conductor_nui ?? '',
                    'fecha_ini' => $this->record->fecha_programada ?: now()->toDateString(),
                    'fecha_fin' => $this->record->fecha_programada ?: now()->addDay()->toDateString(),
                    'origen_local_id' => null,
                    'origen_dir' => \App\Services\Sri\Emisor::dirEstab(),
                    'dest_dir' => $this->record->local_destino_id ? \Illuminate\Support\Facades\DB::table('locales')->where('id', $this->record->local_destino_id)->value('direccion') : '',
                    'motivo' => 'Traslado entre establecimientos propios',
                ])
                ->form([
                    \Filament\Schemas\Components\Section::make('Transportista')->columns(2)->schema([
                        \Filament\Forms\Components\Select::make('transportista_id')->label('Empresa de transporte')->columnSpanFull()
                            ->options(fn () => \Illuminate\Support\Facades\DB::table('transportistas')->where('activo', true)->get()
                                ->mapWithKeys(fn ($t) => [$t->id => ($t->empresa ?: $t->nombre) . ($t->identificacion ? ' · ' . $t->identificacion : '')])->all())
                            ->searchable()->live()
                            ->afterStateUpdated(function ($state, \Filament\Schemas\Components\Utilities\Set $set) {
                                if (! $state) return;
                                $t = \Illuminate\Support\Facades\DB::table('transportistas')->where('id', $state)->first();
                                if ($t) { $set('trans_razon', $t->empresa ?: $t->nombre); $set('trans_ruc', $t->identificacion ?? ''); }
                            })
                            ->helperText('Elige de tu lista o escribe los datos abajo.'),
                        \Filament\Forms\Components\TextInput::make('trans_razon')->label('Transportista / chofer (nombre)')->required(),
                        \Filament\Forms\Components\TextInput::make('trans_ruc')->label('RUC / Cédula transportista')->required(),
                        \Filament\Forms\Components\TextInput::make('chofer_nombre')->label('Chofer (nombre)'),
                        \Filament\Forms\Components\TextInput::make('chofer_cedula')->label('Chofer (cédula)'),
                        \Filament\Forms\Components\TextInput::make('trans_placa')->label('Placa del vehículo'),
                    ]),
                    \Filament\Schemas\Components\Section::make('Fechas de traslado')->columns(2)->schema([
                        \Filament\Forms\Components\DatePicker::make('fecha_ini')->label('Fecha de inicio (carga)')->required(),
                        \Filament\Forms\Components\DatePicker::make('fecha_fin')->label('Fecha fin de traslado')->required(),
                    ]),
                    \Filament\Schemas\Components\Section::make('Ruta')->columns(1)->schema([
                        \Filament\Forms\Components\Select::make('origen_local_id')->label('Desde (local o proveedor conocido, opcional)')
                            ->options(function () {
                                $locales = \Illuminate\Support\Facades\DB::table('locales')->where('activo', true)->get()->mapWithKeys(fn ($l) => ['local:' . $l->id => 'Local: ' . $l->nombre]);
                                $provs = \Illuminate\Support\Facades\DB::table('proveedores')->where('activo', true)->get()->mapWithKeys(fn ($p) => ['prov:' . $p->id => 'Proveedor: ' . $p->nombre]);
                                return $locales->union($provs)->all();
                            })
                            ->searchable()->live()
                            ->afterStateUpdated(function ($state, \Filament\Schemas\Components\Utilities\Set $set) {
                                if (! $state) return;
                                [$tipo, $id] = explode(':', $state, 2);
                                $dir = $tipo === 'local'
                                    ? \Illuminate\Support\Facades\DB::table('locales')->where('id', $id)->value('direccion')
                                    : \Illuminate\Support\Facades\DB::table('proveedores')->where('id', $id)->value('direccion');
                                if ($dir) $set('origen_dir', $dir);
                            })
                            ->helperText('O escribe la dirección directamente abajo si el origen no está en la lista.'),
                        \Filament\Forms\Components\TextInput::make('origen_dir')->label('Desde (dirección de origen)')->required(),
                        \Filament\Forms\Components\Select::make('dest_local_id')->label('Hacia (local/bodega destino, opcional)')
                            ->options(fn () => \Illuminate\Support\Facades\DB::table('locales')->where('activo', true)->pluck('nombre', 'id'))
                            ->searchable()->live()
                            ->afterStateUpdated(function ($state, \Filament\Schemas\Components\Utilities\Set $set) {
                                if (! $state) return;
                                $dir = \Illuminate\Support\Facades\DB::table('locales')->where('id', $state)->value('direccion');
                                if ($dir) $set('dest_dir', $dir);
                            }),
                        \Filament\Forms\Components\TextInput::make('dest_dir')->label('Hacia (dirección del local/bodega destino)')->required(),
                        \Filament\Forms\Components\TextInput::make('motivo')->label('Motivo del traslado')->required(),
                    ]),
                ])
                ->action(function (array $data) {
                    $c = \App\Models\Compra::with('items')->find($this->record->compra_id);
                    if (! $c) { \Filament\Notifications\Notification::make()->danger()->title('Compra no encontrada')->send(); return; }
                    $items = $c->items->map(fn ($it) => ['codigo' => 'ITEM' . $it->id, 'descripcion' => $it->nombre, 'cantidad' => $it->cantidad])->all();
                    $g = [
                        'pedido_id' => null, 'cliente_id' => null, 'despacho_id' => $this->record->id,
                        'dir_partida' => $data['origen_dir'] ?: \App\Services\Sri\Emisor::dirEstab(),

                        'transportista' => [
                            'razon' => $data['trans_razon'], 'ruc' => $data['trans_ruc'],
                            'tipo_id' => strlen(preg_replace('/\D/', '', $data['trans_ruc'])) === 13 ? '04' : '05',
                            'placa' => $data['trans_placa'] ?? '',
                        ],
                        'fecha_ini' => \Illuminate\Support\Carbon::parse($data['fecha_ini'])->format('d/m/Y'),
                        'fecha_fin' => \Illuminate\Support\Carbon::parse($data['fecha_fin'])->format('d/m/Y'),
                        'destinatario' => [
                            'razon' => \App\Services\Sri\Emisor::razon(), 'identificacion' => \App\Services\Sri\Emisor::ruc(),
                            'direccion' => $data['dest_dir'], 'motivo' => $data['motivo'] ?: 'Traslado entre establecimientos propios',
                            'doc_sustento' => [],
                        ],
                        'items' => $items,
                        'info_adicional' => ['Chofer' => trim(($data['chofer_nombre'] ?? '') . ' ' . ($data['chofer_cedula'] ?? '')), 'Compra' => $c->folio ?: ('#' . $c->id)],
                    ];
                    $r = \App\Services\Sri\EmisorGuiaRemision::emitir($g);
                    if ($r['ok'] ?? false) {
                        $this->record->update([
                            'transportista_id' => $data['transportista_id'] ?? $this->record->transportista_id,
                            'conductor_nombre' => $data['chofer_nombre'] ?: $data['trans_razon'],
                            'conductor_nui' => $data['chofer_cedula'] ?? null,
                            'placa' => $data['trans_placa'] ?? null,
                        ]);
                        \App\Models\Compra::where('id', $c->id)->where('estado', 'listo_envio')->update(['estado' => 'en_transito']);
                        if (class_exists(\App\Models\Bitacora::class)) {
                            \App\Models\Bitacora::registrar('emitió guía de remisión', 'Despacho', $this->record->id, $r['numero'] ?? '');
                        }
                        \Filament\Notifications\Notification::make()->success()->title('Guía de remisión autorizada')->body('N° ' . ($r['numero'] ?? ''))->persistent()->send();
                    } else {
                        \Filament\Notifications\Notification::make()->danger()->title('No se pudo emitir la guía')->body($r['msg'] ?? 'Error desconocido.')->persistent()->send();
                    }
                    $this->record->refresh();
                }),
            Actions\Action::make('confirmarRecepcionAbasto')->label('Confirmar recepción')->icon('heroicon-o-check-badge')->color('success')
                ->visible(fn () => $this->record->compra_id && $this->record->estado !== 'entregado'
                    && (\App\Support\Acl::puedeAprobar() || $this->record->empleado_receptor_id === auth()->id()))
                ->requiresConfirmation()
                ->modalHeading('Confirmar recepción de mercadería')
                ->modalDescription('Se sumará el stock al local destino y se marcará la compra/producción como recibida.')
                ->action(function () {
                    $d = $this->record;
                    $c = \App\Models\Compra::with('items')->find($d->compra_id);
                    if (! $c) { \Filament\Notifications\Notification::make()->danger()->title('Compra no encontrada')->send(); return; }
                    foreach ($c->items as $it) {
                        \App\Models\MovimientoStock::create([
                            'producto_id' => $it->producto_id, 'variante_id' => $it->variante_id,
                            'local_id' => $d->local_destino_id ?: $c->local_destino_id,
                            'tipo' => 'entrada', 'cantidad' => (int) $it->cantidad,
                            'referencia' => $c->folio ?: ('compra-' . $c->id),
                            'nota' => 'Recepción confirmada por ' . (auth()->user()->name ?? '—') . ' · ' . ($c->folio ?: ''),
                        ]);
                    }
                    $c->update(['estado' => 'recibida', 'recibida_at' => now()]);
                    $d->update(['estado' => 'entregado', 'entregado_at' => now()]);
                    if (class_exists(\App\Models\Bitacora::class)) {
                        \App\Models\Bitacora::registrar('confirmó recepción de despacho', 'Despacho', $d->id, ($d->folio ?: '') . ' · stock sumado');
                    }
                    \Filament\Notifications\Notification::make()->success()->title('Recepción confirmada')->body('Stock actualizado.')->send();
                    $this->record->refresh();
                }),
            // ===== VENTA DIRECTA: entrega/retiro con firma (sin SRI guía, sin pedido) =====
            Actions\Action::make('entregarVenta')
                ->label($esRetiro ? 'Registrar retiro (con firma)' : 'Marcar entregado (con firma)')
                ->icon('heroicon-o-check-circle')->color('success')
                ->visible(fn () => $this->record->venta_id && \App\Support\Acl::puedeEntregar() && $this->record->estado !== 'entregado')
                ->modalHeading($esRetiro ? 'Registrar retiro del cliente' : 'Entrega de la venta')
                ->modalWidth('lg')
                ->form([
                    \Filament\Forms\Components\TextInput::make('recibido_nombre')->label('Nombre de quien recibe')->required(),
                    \Filament\Forms\Components\TextInput::make('recibido_cedula')->label('Cédula de quien recibe'),
                    \Filament\Forms\Components\ViewField::make('firma_cliente')->label('Firma')->view('filament.forms.firma-pad'),
                ])
                ->action(function (array $data) {
                    $record = $this->record;
                    $record->update([
                        'estado' => 'entregado',
                        'recibido_nombre' => $data['recibido_nombre'] ?? null,
                        'recibido_cedula' => $data['recibido_cedula'] ?? null,
                        'firma_cliente' => $data['firma_cliente'] ?? null,
                        'entregado_at' => now(),
                    ]);
                    $venta = \Illuminate\Support\Facades\DB::table('ventas')->where('id', $record->venta_id)->first();
                    $folio = $venta->numero_comprobante ?? ('#' . $record->venta_id);
                    if (class_exists(\App\Models\Bitacora::class)) {
                        \App\Models\Bitacora::registrar('entregó venta', 'Despacho', $record->id, $folio . ' a ' . ($data['recibido_nombre'] ?? ''));
                    }
                    $cli = $venta && $venta->cliente_id ? \Illuminate\Support\Facades\DB::table('clientes')->where('id', $venta->cliente_id)->first() : null;
                    if ($cli && $cli->email) {
                        $cuerpo = '<p>Tu compra <strong>' . $folio . '</strong> fue ' . ($esRetiro ? 'retirada' : 'entregada') . ' correctamente.</p>';
                        $html = \App\Support\CorreoBrand::wrap('Entrega confirmada', $cuerpo);
                        try { \Illuminate\Support\Facades\Mail::to($cli->email)->send(new \App\Mail\DocumentoPedido('Entrega confirmada · ' . $folio, $html, [])); } catch (\Throwable $e) { report($e); }
                    }
                    \Filament\Notifications\Notification::make()->success()->title($esRetiro ? 'Retiro registrado' : 'Venta entregada')->send();
                }),
            Actions\Action::make('entregar')
                ->label($esRetiro ? 'Registrar retiro (con firma)' : 'Marcar entregado (con firma)')
                ->icon('heroicon-o-check-circle')->color('success')
                ->visible(fn () => ! $this->record->compra_id && ! $this->record->venta_id && \App\Support\Acl::puedeEntregar() && $this->record->estado !== 'entregado' && ! $this->saldoPendiente())
                ->modalHeading($esRetiro ? 'Registrar retiro del cliente' : 'Entrega del pedido al cliente')
                ->modalWidth('lg')
                ->form([
                    \Filament\Forms\Components\TextInput::make('recibido_nombre')->label('Nombre de quien recibe')->required(),
                    \Filament\Forms\Components\TextInput::make('recibido_cedula')->label('Cédula de quien recibe'),
                    \Filament\Forms\Components\ViewField::make('firma_cliente')->label('Firma')->view('filament.forms.firma-pad'),
                ])
                ->action(function (array $data) {
                    $record = $this->record;
                    $record->update([
                        'estado' => 'entregado',
                        'recibido_nombre' => $data['recibido_nombre'] ?? null,
                        'recibido_cedula' => $data['recibido_cedula'] ?? null,
                        'firma_cliente' => $data['firma_cliente'] ?? null,
                        'entregado_at' => now(),
                    ]);
                    $ped = $this->pedido();
                    if ($ped) {
                        \App\Services\EstadoPedidoErp::avanzar($ped, 'entregado');
                        \App\Services\Traza::registrar($ped, 'recibido', 'Recibido por ' . ($data['recibido_nombre'] ?? '—'));
                    }
                    $abs = \App\Services\PdfErp::actaEntregaPedido($record->fresh(), $data['firma_cliente'] ?? null);
                    $dest = [];
                    if ($ped && $ped->cliente_id) {
                        $em = \Illuminate\Support\Facades\DB::table('clientes')->where('id', $ped->cliente_id)->value('email');
                        if ($em) $dest[] = $em;
                    }
                    foreach (\Illuminate\Support\Facades\DB::table('users')->whereIn('rol', ['operaciones','admin'])->where('activo', true)->pluck('email') as $e) $dest[] = $e;
                    $folio = $ped->folio ?? ('#' . $record->pedido_id);
                    $cuerpo = '<p>Acta de entrega del pedido <strong>' . $folio . '</strong>. Adjuntamos el comprobante firmado.</p>';
                    $html = \App\Support\CorreoBrand::wrap('Acta de entrega', $cuerpo);
                    foreach (array_unique(array_filter($dest)) as $to) {
                        try { \Illuminate\Support\Facades\Mail::to($to)->send(new \App\Mail\DocumentoPedido('Acta de entrega · ' . $folio, $html, [$abs])); } catch (\Throwable $e) { report($e); }
                    }
                    \App\Models\Bitacora::registrar('entregó pedido', 'Despacho', $record->id, $folio . ' a ' . ($data['recibido_nombre'] ?? ''));
                    \Filament\Notifications\Notification::make()->success()->title($esRetiro ? 'Retiro registrado' : 'Pedido entregado')->body('Acta firmada generada y enviada.')->send();
                    return response()->download($abs, 'acta-entrega-' . $folio . '.pdf');
                }),
            Actions\Action::make('editar')
                ->label('Editar datos')->icon('heroicon-o-pencil')->color('gray')
                ->url(fn () => DespachoResource::getUrl('edit', ['record' => $this->record])),
        ];
    }

    protected function getViewData(): array
    {
        $d = $this->record;
        $userName = fn ($id) => $id ? DB::table('users')->where('id', $id)->value('name') : null;
        $localRetiro = $d->local_retiro_id ? DB::table('locales')->where('id', $d->local_retiro_id)->value('nombre') : null;
        $transportista = $d->transportista_id ? DB::table('transportistas')->where('id', $d->transportista_id)->value('nombre') : null;

        if ($d->venta_id) {
            $venta = DB::table('ventas')->where('id', $d->venta_id)->first();
            $cliente = $venta && $venta->cliente_id ? DB::table('clientes')->where('id', $venta->cliente_id)->first() : null;
            $items = collect(json_decode($d->detalle_json ?: '[]'))->map(fn ($it) => (object) ['nombre' => $it->nombre ?? '—', 'cantidad' => $it->cantidad ?? 1]);
            return [
                'd' => $d, 'p' => (object) ['folio' => $venta->numero_comprobante ?? ('#' . $d->venta_id), 'nro_factura' => null, 'destino_fab' => null],
                'cliente' => $cliente, 'items' => $items,
                'localRetiro' => $localRetiro, 'transportista' => $transportista,
                'saldo' => 0, 'esRetiro' => $d->ruta === 'retiro_local',
                'vendidoPor' => $userName($venta->vendedor_id ?? null), 'aprobadoPor' => null,
            ];
        }

        if ($d->compra_id) {
            $compra = \App\Models\Compra::find($d->compra_id);
            $nombreOrigen = $compra && $compra->proveedor_id ? DB::table('proveedores')->where('id', $compra->proveedor_id)->value('nombre') : 'Bodega propia';
            $items = collect(json_decode($d->detalle_json ?: '[]'))->map(fn ($it) => (object) ['nombre' => $it->nombre ?? '—', 'cantidad' => $it->cantidad ?? 1]);
            return [
                'd' => $d, 'p' => (object) ['folio' => $compra->folio ?? ('#' . $d->compra_id), 'nro_factura' => null, 'destino_fab' => $compra && $compra->tipo === 'produccion_interna' ? 'interno' : 'externo'],
                'cliente' => (object) ['nombre' => $nombreOrigen, 'celular' => null, 'telefono' => null, 'email' => null, 'cedula_ruc' => null, 'identificacion' => null],
                'items' => $items,
                'localRetiro' => $localRetiro, 'transportista' => $transportista,
                'saldo' => 0, 'esRetiro' => $d->ruta === 'retiro_local',
                'vendidoPor' => null, 'aprobadoPor' => $userName($d->empleado_receptor_id ?? null),
            ];
        }

        $p = $this->pedido();
        $cliente = ($p && $p->cliente_id) ? DB::table('clientes')->where('id', $p->cliente_id)->first() : null;
        $items = $p ? DB::table('pedido_items')->where('pedido_id', $p->id)->get() : collect();

        return [
            'd'            => $d,
            'p'            => $p,
            'cliente'      => $cliente,
            'items'        => $items,
            'localRetiro'  => $localRetiro,
            'transportista'=> $transportista,
            'saldo'        => $p ? RecibosErp::saldo($p) : 0,
            'esRetiro'     => $d->ruta === 'retiro_local',
            'vendidoPor'   => $userName($p->vendido_por ?? null) ?? $userName($p->vendedor_id ?? null),
            'aprobadoPor'  => $userName($p->aprobado_por ?? null),
        ];
    }
}
