<?php
namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;


use App\Support\Acl;
use App\Filament\Resources\ReciboResource\Pages;
use App\Models\Pedido;
use App\Models\Recibo;
use App\Services\RecibosErp;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReciboResource extends Resource
{
    protected static ?string $model = Recibo::class;

    public static function canViewAny(): bool
    {
        return Acl::ve(static::class);
    }
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';
    protected static string|\UnitEnum|null $navigationGroup = 'Ventas';
    protected static ?string $modelLabel = 'Recibo';
    protected static ?string $pluralModelLabel = 'Recibos de pago';
    protected static ?int $navigationSort = 5;

    public static function getEloquentQuery(): Builder
    {
        $q = Recibo::query()->with(['cliente']);
        if (! Acl::esAdmin() && Acl::rol() === 'vendedor') {
            $q->whereIn('pedido_id', \App\Models\Pedido::where('vendedor_id', auth()->id())->pluck('id'));
        }
        return $q;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('folio')->label('Folio')->disabled()->placeholder('(se genera al guardar)'),
                Forms\Components\Select::make('pedido_id')->label('Pedido')->required()->live()->searchable()
                    ->options(fn () => Pedido::query()->latest()->limit(300)->get()
                        ->mapWithKeys(fn ($p) => [$p->id => ($p->folio ?: ('#' . $p->id)) . ' · ' . $p->codigo . ' · $' . number_format((float) $p->total, 2)])->all())
                    // si el cobro se ingresa desde un pedido, queda bloqueado para no cambiarlo por error
                    ->disabled(fn () => filled(request('pedido_id')))
                    ->dehydrated(true)
                    ->helperText(fn () => filled(request('pedido_id')) ? 'Cobro asociado a este pedido (no editable).' : null),
                Forms\Components\Placeholder::make('saldo_info')->label('Estado de pago')
                    ->content(function (\Filament\Schemas\Components\Utilities\Get $get) {
                        $pid = $get('pedido_id');
                        if (! $pid || ! ($p = Pedido::find($pid))) return '—';
                        return 'Total $' . number_format((float) $p->total, 2)
                            . ' · Pagado $' . number_format(RecibosErp::pagado($p), 2)
                            . ' · Saldo $' . number_format(RecibosErp::saldo($p), 2);
                    }),
                Forms\Components\Select::make('tipo')->label('Tipo')->options(['abono' => 'Abono', 'pago' => 'Pago'])->default('abono')->required(),
                Forms\Components\TextInput::make('monto')->numeric()->prefix('$')->required()->minValue(0),
                Forms\Components\Select::make('metodo')->label('Método')->live()->options([
                    'efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'tarjeta' => 'Tarjeta', 'deposito' => 'Depósito', 'cheque' => 'Cheque', 'otro' => 'Otro',
                ]),
                // N° comprobante: transferencia / depósito
                Forms\Components\TextInput::make('nro_comprobante')->label('N° de comprobante')
                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => in_array($get('metodo'), ['transferencia', 'deposito'], true))
                    ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => in_array($get('metodo'), ['transferencia', 'deposito'], true)),
                // Tarjeta: lote + tipo
                Forms\Components\TextInput::make('lote')->label('Lote')
                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('metodo') === 'tarjeta'),
                Forms\Components\Select::make('tarjeta_naturaleza')->label('Débito o crédito')
                    ->options(['debito' => 'Tarjeta de débito', 'credito' => 'Tarjeta de crédito'])
                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('metodo') === 'tarjeta')
                    ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('metodo') === 'tarjeta')
                    ->helperText('Define el código de forma de pago ante el SRI.'),
                Forms\Components\Select::make('tipo_tarjeta')->label('Marca (opcional)')
                    ->options(['visa' => 'Visa', 'mastercard' => 'Mastercard', 'diners' => 'Diners Club', 'discover' => 'Discover', 'amex' => 'American Express'])
                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('metodo') === 'tarjeta'),
                // Cheque: girador, número, banco, fecha de cobro
                \Filament\Schemas\Components\Fieldset::make('Datos del cheque')->columns(2)->columnSpanFull()
                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('metodo') === 'cheque')
                    ->schema([
                        Forms\Components\TextInput::make('cheque_girador')->label('Girado por (titular)')
                            ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('metodo') === 'cheque'),
                        Forms\Components\TextInput::make('cheque_numero')->label('N° de cheque')
                            ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('metodo') === 'cheque'),
                        Forms\Components\TextInput::make('cheque_banco')->label('Banco')
                            ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('metodo') === 'cheque'),
                        Forms\Components\DatePicker::make('cheque_fecha_cobro')->label('Fecha de cobro')
                            ->helperText('Cuándo se puede cobrar el cheque.')
                            ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('metodo') === 'cheque'),
                    ]),
                // Efectivo / Otro: quién recibe
                Forms\Components\TextInput::make('recibido_por')->label('Recibido / autorizado por')
                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => in_array($get('metodo'), ['efectivo', 'otro'], true)),
                Forms\Components\DatePicker::make('fecha')->default(now()),
                \Filament\Schemas\Components\Fieldset::make('Quién paga (si es distinto al cliente)')->columns(2)->columnSpanFull()->schema([
                    Forms\Components\TextInput::make('pagador_nombre')->label('Nombre del pagador'),
                    Forms\Components\TextInput::make('pagador_id_num')->label('Cédula / RUC'),
                    Forms\Components\TextInput::make('pagador_contacto')->label('Teléfono'),
                    Forms\Components\TextInput::make('pagador_email')->label('Email')->email()->helperText('Si lo llenas, también recibe el comprobante.'),
                ]),
                Forms\Components\Textarea::make('nota')->rows(2)->columnSpanFull(),
                Forms\Components\FileUpload::make('comprobantes')->label('Comprobantes (fotos)')->image()->multiple()
                    ->directory('comprobantes')->disk('public')->maxFiles(5)->columnSpanFull()
                    ->helperText('Sube foto(s) del comprobante (transferencia, depósito, etc.).'),
                Forms\Components\Toggle::make('notificar')->label('Avisar al cliente por correo')->default(true)->dehydrated(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('folio')->label('Folio')->sortable()->searchable()->placeholder('—'),
                Tables\Columns\TextColumn::make('pedido_id')->label('Pedido')->searchable()
                    ->formatStateUsing(fn ($state, $record) => $record->pedido?->folio ?: ('#' . $state))
                    ->url(fn ($record) => $record->pedido_id ? \App\Filament\Resources\PedidoEspecialResource::getUrl('view', ['record' => $record->pedido_id]) : null)
                    ->color('primary')->weight('bold')->openUrlInNewTab(),
                Tables\Columns\TextColumn::make('cliente.nombre')->label('Cliente')->placeholder('—'),
                Tables\Columns\TextColumn::make('tipo')->badge()->formatStateUsing(fn ($state) => ucfirst((string) $state)),
                Tables\Columns\TextColumn::make('monto')->money('usd')->sortable(),
                Tables\Columns\TextColumn::make('metodo')->placeholder('—'),
                Tables\Columns\TextColumn::make('saldo')->label('Saldo pedido')->state(function (Recibo $r) {
                    return $r->pedido ? '$' . number_format(RecibosErp::saldo($r->pedido), 2) : '—';
                }),
                Tables\Columns\IconColumn::make('validado')->label('Validado')->boolean(),
                Tables\Columns\TextColumn::make('resolucion_estado')->label('Resolución')->badge()
                    ->state(function (\App\Models\Recibo $r) {
                        if ($r->resolucion) return ['nota_credito'=>'Nota de crédito','saldo_favor'=>'Saldo a favor','reembolso'=>'Reembolso'][$r->resolucion] ?? $r->resolucion;
                        $ped = \App\Models\PedidoEspecial::find($r->pedido_id);
                        return ($ped && in_array($ped->estado_erp, ['anulado','cancelado'], true) && $r->validado) ? 'Resolución pendiente' : null;
                    })
                    ->color(fn ($state) => $state === 'Resolución pendiente' ? 'danger' : 'gray')->placeholder('—'),
                Tables\Columns\TextColumn::make('fecha')->date('d/m/Y')->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->recordUrl(fn (\App\Models\Recibo $record) => Pages\ViewRecibo::getUrl(['record' => $record->id]))
            ->actions([
                Actions\Action::make('resolver')->label('Resolver pago')->icon('heroicon-o-scale')->color('warning')
                    ->visible(function (\App\Models\Recibo $r) {
                        if (! \App\Support\Acl::puedeResolverPago()) return false;
                        if ($r->resolucion) return false;
                        if (! $r->validado) return false;
                        $ped = \App\Models\PedidoEspecial::find($r->pedido_id);
                        return $ped && in_array($ped->estado_erp, ['anulado', 'cancelado'], true);
                    })
                    ->modalHeading('Resolver pago de pedido anulado')
                    ->form([
                        Forms\Components\Select::make('tipo')->label('Destino del pago')->required()
                            ->options(['nota_credito' => 'Nota de crédito', 'saldo_favor' => 'Saldo a favor (crédito cliente)', 'reembolso' => 'Reembolso (devolución)']),
                        Forms\Components\Textarea::make('nota')->label('Nota / referencia')->rows(2),
                    ])
                    ->action(function (\App\Models\Recibo $record, array $data) {
                        $res = \App\Services\ResolucionPago::resolver($record, $data['tipo'], $data['nota'] ?? null);
                        \Filament\Notifications\Notification::make()->success()->title('Pago resuelto: ' . $res['tipo'])->send();
                    }),
                Actions\Action::make('validar')->label('Validar pago')->icon('heroicon-o-check-badge')->color('success')
                    ->visible(fn (\App\Models\Recibo $r) => \App\Support\Acl::puedeValidarPago() && ! $r->validado)
                    ->requiresConfirmation()
                    ->modalHeading('Validar pago')
                    ->modalDescription(fn (\App\Models\Recibo $r) => 'Confirmar el pago de $' . number_format((float) $r->monto, 2) . ' por ' . ucfirst((string) $r->metodo) . '. Esto permite avanzar el pedido.')
                    ->action(function (\App\Models\Recibo $record) {
                        $ped = \App\Models\PedidoEspecial::find($record->pedido_id);
                        if (! $ped) { \Filament\Notifications\Notification::make()->danger()->title('Pedido no encontrado')->send(); return; }
                        $record->update(['validado' => true, 'validado_por' => auth()->id(), 'validado_at' => now()]);
                        \App\Services\RecibosErp::validar($record->fresh(), $ped);
                        \App\Models\Bitacora::registrar('validó pago', 'Recibo', $record->id, '$' . number_format((float) $record->monto, 2) . ' · ' . $record->metodo);
                        \Filament\Notifications\Notification::make()->success()->title('Pago validado')->send();
                    }),
                Actions\EditAction::make(),
                Actions\DeleteAction::make()->visible(fn () => \App\Support\Acl::puedeEliminar()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRecibos::route('/'),
            'create' => Pages\CreateRecibo::route('/create'),
            'view'   => Pages\ViewRecibo::route('/{record}'),
            'edit'   => Pages\EditRecibo::route('/{record}/edit'),
        ];
    }
}
