<?php
namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;


use App\Support\Acl;
use App\Filament\Resources\PedidoEspecialResource\Pages;
use App\Filament\Resources\PedidoEspecialResource\RelationManagers\ItemsRelationManager;
use App\Models\PedidoEspecial;
use App\Services\EstadoPedidoErp;
use App\Services\Traza;
use App\Models\Proveedor;
use App\Services\FlujoErp;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PedidoEspecialResource extends Resource
{
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return \App\Support\Acl::esAdmin();
    }
    protected static ?string $model = PedidoEspecial::class;

    public static function canViewAny(): bool
    {
        return Acl::ve(static::class);
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        // V471_GUARDS + V_EDIT_EARLY: editar solo en estados tempranos (antes de aprobar/fabricar)
        $tempranos = ['borrador', 'pendiente', 'por_aprobar'];
        if (! in_array($record->estado_erp, $tempranos, true)) return false;
        if (\App\Support\Acl::esAdmin() || \App\Support\Acl::esOperaciones()) return true;
        if (\App\Support\Acl::esVendedor()) return (int) $record->vendedor_id === (int) auth()->id();
        return false;
    }
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static string|\UnitEnum|null $navigationGroup = 'Ventas';
    protected static ?string $modelLabel = 'Pedido';
    protected static ?string $pluralModelLabel = 'Pedidos';
    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        $q = PedidoEspecial::query();
        if (! Acl::esAdmin() && Acl::rol() === 'vendedor') {
            $q/* V470_VENDEDOR_VE_TODOS: filtro retirado */;
        }
        return $q;
    }

    public static function getNavigationBadge(): ?string
    {
        if (! \App\Support\Acl::puedeAprobar()) return null;
        $n = \App\Models\PedidoEspecial::where('estado_erp', 'por_aprobar')->count();
        return $n ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make()->columns(2)->columnSpanFull()->schema([
                Forms\Components\TextInput::make('folio')->label('Folio')->disabled(),
                Forms\Components\Select::make('estado_erp')->label('Estado')->options(EstadoPedidoErp::ESTADOS)->default('pendiente')->disabled()->dehydrated(false)->helperText('El estado avanza automáticamente con el flujo.'),
                Forms\Components\Select::make('tipo_erp')->label('Tipo')->options([
                    'pedido_especial' => 'Pedido especial (local)', 'online' => 'Venta online', 'local' => 'Venta local',
                ])->placeholder('—')->disabled(fn (?PedidoEspecial $record) => $record && in_array($record->estado_erp, ['aprobado','enviado_proveedor','en_fabricacion','en_produccion','listo_proveedor','en_bodega','listo_despacho','despachado','entregado','anulado','cancelado'], true)), // V_LOCK_FAB
                Forms\Components\TextInput::make('nro_contable')->label('N° contable')->disabled(fn (?PedidoEspecial $record) => $record && in_array($record->estado_erp, ['aprobado','enviado_proveedor','en_fabricacion','en_produccion','listo_proveedor','en_bodega','listo_despacho','despachado','entregado','anulado','cancelado'], true) && ! \App\Support\Acl::puedeAprobar()),
                Forms\Components\TextInput::make('nro_factura')->label('N° factura')->disabled(fn (?PedidoEspecial $record) => $record && in_array($record->estado_erp, ['aprobado','enviado_proveedor','en_fabricacion','en_produccion','listo_proveedor','en_bodega','listo_despacho','despachado','entregado','anulado','cancelado'], true) && ! \App\Support\Acl::puedeAprobar()),
                Forms\Components\DatePicker::make('fecha_solicitada')->label('Fecha solicitada (cliente)')->disabled(),
                Forms\Components\DatePicker::make('fecha_comprometida')->label('Fecha comprometida')->disabled(fn () => ! \App\Support\Acl::puedeAprobar()),
                Forms\Components\TextInput::make('folio_of')->label('Orden(es) de fabricación')->disabled(),
            ]),
            \Filament\Schemas\Components\Section::make('Datos de cliente y entrega')->columns(2)->columnSpanFull()->schema([
                Forms\Components\Placeholder::make('cliente_nombre')->label('Cliente')
                    ->content(fn (?PedidoEspecial $record) => $record && $record->cliente ? $record->cliente->nombre : '—'),
                Forms\Components\Placeholder::make('cliente_contacto')->label('Contacto')
                    ->content(fn (?PedidoEspecial $record) => $record && $record->cliente ? trim(($record->cliente->celular ?? $record->cliente->telefono ?? '') . ' · ' . ($record->cliente->email ?? ''), ' ·') : '—'),
                Forms\Components\Placeholder::make('entrega_modo')->label('Entrega')
                    ->content(fn (?PedidoEspecial $record) => $record && $record->retira_local ? 'Retira en local' : 'Envío a domicilio'),
                Forms\Components\Placeholder::make('entrega_dir')->label('Dirección de envío')
                    ->content(fn (?PedidoEspecial $record) => $record && ! $record->retira_local ? trim(($record->direccion_envio ?? '') . ' · ' . ($record->ciudad_envio ?? '') . ' · ' . ($record->contacto_envio ?? ''), ' ·') ?: '—' : '—')
                    ->columnSpanFull(),
            ]),
            \Filament\Schemas\Components\Section::make('Anulación')
                ->visible(fn (?PedidoEspecial $record) => $record && filled($record->observacion_anulacion))
                ->schema([
                    Forms\Components\TextInput::make('folio_anulacion')->label('Folio de anulación')->disabled(),
                    Forms\Components\Textarea::make('observacion_anulacion')->label('Motivo de anulación')->disabled()->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('folio')->label('Folio')->sortable()->searchable()->placeholder('—'),
                Tables\Columns\TextColumn::make('tipo_erp')->label('Tipo')->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pedido_especial' => 'Especial', 'online' => 'Online', 'local' => 'Local', default => '—',
                    })->color('gray'),
                Tables\Columns\TextColumn::make('estado_erp')->label('Estado')->badge()
                    ->formatStateUsing(fn ($state) => EstadoPedidoErp::ESTADOS[$state] ?? ($state ?: '—'))
                    ->color(fn ($state) => match ($state) {
                        'en_bodega', 'listo_despacho', 'despachado', 'entregado' => 'ok',
                        'pendiente', 'por_aprobar' => 'warning',
                        'en_produccion' => 'taller',
                        'anulado', 'cancelado' => 'danger',
                        'borrador', null => 'gray',
                        default => 'info',
                    }),
                Tables\Columns\TextColumn::make('fecha_comprometida')->label('Entrega')->date('d/m/Y')->placeholder('—')->sortable()
                    ->state(fn (PedidoEspecial $r) => $r->fecha_comprometida ?: $r->fecha_solicitada)
                    ->description(fn (PedidoEspecial $r) => $r->fecha_comprometida ? 'comprometida' : ($r->fecha_solicitada ? 'solicitada' : null)),
                Tables\Columns\TextColumn::make('estado_pago_col')->label('Pago')->badge()
                    ->state(function (PedidoEspecial $r) {
                        $saldo = \App\Services\RecibosErp::saldo($r);
                        return $saldo <= 0 ? 'Pagado' : ('Debe $' . number_format($saldo, 2));
                    })
                    ->color(fn ($state) => $state === 'Pagado' ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('total')->money('usd')->label('Total'),
            ])
            ->defaultSort('id', 'desc')
            ->actions([
                Actions\Action::make('aDespacho')->label('Enviar a despacho')->icon('heroicon-o-truck')->color('success')
                    ->visible(fn (PedidoEspecial $r) => $r->estado_erp === 'en_bodega')
                    ->requiresConfirmation()->modalDescription('Pasa el pedido a despacho. Requiere estar pagado.')
                    ->action(function (PedidoEspecial $record) {
                        if (\App\Services\RecibosErp::saldo($record) > 0) {
                            Notification::make()->danger()->title('No se puede enviar a despacho')->body('El pedido tiene saldo pendiente. Registra el pago primero.')->send();
                            return;
                        }
                        EstadoPedidoErp::avanzar($record, 'listo_despacho');
                        \App\Services\Traza::registrar($record, 'listo_despacho', 'Enviado a despacho desde bodega');
                        Notification::make()->success()->title('Enviado a despacho')->body('Se generó el despacho del pedido.')->send();
                    }),
                Actions\Action::make('proveedor')->label('Enviar a proveedor')->icon('heroicon-o-paper-airplane')->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (PedidoEspecial $r) => in_array($r->estado_erp, ['aprobado', 'confirmado'], true))
                    ->action(function (PedidoEspecial $record) {
                        $r = EstadoPedidoErp::enviarAProveedor($record);
                        if ($r['ok'] ?? false) Notification::make()->success()->title('Enviado a proveedor')->body(implode(', ', $r['proveedores'] ?: ['sin email']))->send();
                        else Notification::make()->danger()->title($r['msg'] ?? 'Error')->send();
                    }),
                Actions\Action::make('anular')->label('Anular')->icon('heroicon-o-x-circle')->color('danger')
                    ->visible(fn (PedidoEspecial $r) => ! in_array($r->estado_erp, ['anulado', 'cancelado', 'entregado'], true))
                    ->modalHeading('Anular pedido')
                    ->form([
                        Forms\Components\Textarea::make('observacion')->label('Motivo de la anulación')->required()->rows(3),
                        Forms\Components\Toggle::make('notificar')->label('Avisar al cliente por correo')->default(true),
                    ])
                    ->requiresConfirmation()
                    ->action(function (PedidoEspecial $record, array $data) {
                        EstadoPedidoErp::anular($record, $data['observacion'], (bool) ($data['notificar'] ?? true));
                        Notification::make()->success()->title('Pedido anulado')->send();
                    }),
                Actions\Action::make('aprobacion')->label('Enviar a aprobación')->icon('heroicon-o-paper-airplane')->color('warning')
                    ->visible(fn (PedidoEspecial $r) => Acl::esVendedor() && in_array($r->estado_erp, [null, 'borrador', 'pendiente'], true))
                    ->requiresConfirmation()
                    ->action(function (PedidoEspecial $record) {
                        EstadoPedidoErp::avanzar($record, 'por_aprobar', false);
                        Traza::registrar($record, 'enviado_aprobacion');
                        FlujoErp::alarmaPorAprobar($record->fresh());
                        \Filament\Notifications\Notification::make()->success()->title('Enviado a aprobación')->send();
                    }),
                Actions\Action::make('aprobar')->label('Aprobar y enviar a fabricación')->icon('heroicon-o-check-circle')->color('success')
                    ->visible(fn (PedidoEspecial $r) => Acl::puedeAprobar() && $r->estado_erp === 'por_aprobar')
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
                        Forms\Components\DatePicker::make('fecha_comprometida')->label('Fecha comprometida (opcional, por defecto la del cliente)')->minDate(now())
                            ->default(fn () => null)->helperText('Si la dejas vacía, se usa la fecha solicitada por el cliente.'),
                    ])
                    ->action(function (PedidoEspecial $record, array $data) {
                        $destino = $data['destino_fab'] ?? 'proveedor';
                        if ($destino === 'proveedor' && ! empty($data['proveedor_id'])) {
                            \Illuminate\Support\Facades\DB::table('pedido_items')->where('pedido_id', $record->id)->update(['proveedor_id' => $data['proveedor_id']]);
                        }
                        \Illuminate\Support\Facades\DB::table('pedidos')->where('id', $record->id)->update(['fecha_comprometida' => $data['fecha_comprometida']]);
                        $res = FlujoErp::aprobar($record->fresh(), $destino);
                        if ($res['ok']) \Filament\Notifications\Notification::make()->success()->title($res['msg'])->send();
                        else \Filament\Notifications\Notification::make()->danger()->title($res['msg'])->send();
                    }),
                Actions\Action::make('fabricacion')->label('Enviar a fabricación')->icon('heroicon-o-wrench-screwdriver')->color('success')
                    ->visible(fn (PedidoEspecial $r) => false)
                    ->requiresConfirmation()
                    ->action(function (PedidoEspecial $record) {
                        $res = EstadoPedidoErp::enviarAProveedor($record);
                        Traza::registrar($record, 'enviado_fabricacion');
                        if ($res['ok'] ?? false) \Filament\Notifications\Notification::make()->success()->title('Enviado a fabricación')->body(implode(', ', $res['proveedores'] ?: ['sin email']))->send();
                        else \Filament\Notifications\Notification::make()->danger()->title($res['msg'] ?? 'Error')->send();
                    }),
                Actions\Action::make('cambiarFecha')->label('Cambiar fecha')->icon('heroicon-o-calendar')->color('warning')
                    ->visible(fn (PedidoEspecial $r) => Acl::puedeAprobar() && in_array($r->estado_erp, ['aprobado', 'enviado_proveedor', 'en_fabricacion', 'listo_proveedor'], true))
                    ->form([
                        Forms\Components\DatePicker::make('fecha_comprometida')->label('Nueva fecha comprometida')->required()->minDate(now()),
                        Forms\Components\Textarea::make('motivo')->label('Motivo del cambio')->rows(2),
                    ])
                    ->action(function (PedidoEspecial $record, array $data) {
                        FlujoErp::cambiarFecha($record, $data['fecha_comprometida'], $data['motivo'] ?? null);
                        \Filament\Notifications\Notification::make()->success()->title('Fecha actualizada')->body('Se notificó a vendedor y cliente.')->send();
                    }),
                Actions\EditAction::make()
                    ->visible(fn (PedidoEspecial $r) => static::canEdit($r)),
                Actions\ViewAction::make()
                    ->url(fn (PedidoEspecial $r) => static::getUrl('view', ['record' => $r])),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
            \App\Filament\Resources\PedidoEspecialResource\RelationManagers\HistorialRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPedidoEspecial::route('/'),
            'view'  => Pages\ViewPedidoEspecial::route('/{record}'),
            'edit'  => Pages\EditPedidoEspecial::route('/{record}/edit'),
        ];
    }
}
