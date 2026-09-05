<?php
namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;


use App\Support\Acl;
use App\Filament\Resources\DespachoResource\Pages;
use App\Models\Despacho;
use App\Models\Transportista;
use App\Services\DespachoErp;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class DespachoResource extends Resource
{
    protected static ?string $model = Despacho::class;


    public static function canViewAny(): bool
    {
        return Acl::ve(static::class);
    }
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $q = \App\Models\Despacho::query()->with(['pedido']);
        if (! Acl::puedeAprobar()) {
            $q->where(function ($w) {
                $w->where('tipo', '!=', 'abastecimiento')
                  ->orWhere('empleado_receptor_id', auth()->id());
            });
        }
        return $q;
    }
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-paper-airplane';
    protected static string|\UnitEnum|null $navigationGroup = 'Logística';
    protected static ?string $modelLabel = 'Despacho';
    protected static ?string $pluralModelLabel = 'Despachos';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('pedido_id')->label('N° de pedido')->numeric()->required()->disabled()->dehydrated()->helperText('Viene encadenado desde la venta; no se modifica.'),
                Forms\Components\Select::make('ruta')->options([
                    'retiro_local' => 'Retiro en local', 'transportista' => 'Transportista',
                ])->default('retiro_local')->required()->live(),
                Forms\Components\Select::make('transportista_id')->label('Empresa de transporte')
                    ->options(fn () => Transportista::where('activo', true)->pluck('nombre', 'id'))
                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('ruta') === 'transportista'),
                Forms\Components\Select::make('local_retiro_id')->label('Local de retiro')
                    ->options(fn () => DB::table('locales')->pluck('nombre', 'id'))
                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('ruta') === 'retiro_local'),
                Forms\Components\DateTimePicker::make('fecha_programada')->seconds(false),
                Forms\Components\Textarea::make('notas')->rows(2)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('tipo')->label('')->badge()->formatStateUsing(fn ($state) => $state === 'garantia' ? 'Garantía' : null)->color('warning')->placeholder(''),
            Tables\Columns\TextColumn::make('folio')->label('Despacho')->searchable()->placeholder('—'),
            Tables\Columns\TextColumn::make('pedido.folio')->label('Pedido / Factura')->searchable()
                ->state(function (\App\Models\Despacho $d) {
                    if ($d->tipo === 'abastecimiento') return \Illuminate\Support\Facades\DB::table('compras')->where('id', $d->compra_id)->value('folio') ?: '—';
                    if ($d->tipo === 'venta_directa') return \Illuminate\Support\Facades\DB::table('ventas')->where('id', $d->venta_id)->value('numero_comprobante') ?: '—';
                    return $d->pedido->folio ?? '—';
                })
                ->description(function (\App\Models\Despacho $d) {
                    if ($d->tipo === 'abastecimiento') return 'Abastecimiento';
                    if ($d->tipo === 'venta_directa') return 'Venta de stock';
                    return optional(\App\Models\PedidoEspecial::find($d->pedido_id))->nro_factura;
                }),
            Tables\Columns\TextColumn::make('cliente_venta_col')->label('Cliente')->placeholder('—')
                ->state(function (\App\Models\Despacho $d) {
                    if ($d->tipo !== 'venta_directa') return null;
                    $v = \Illuminate\Support\Facades\DB::table('ventas')->where('id', $d->venta_id)->first();
                    return $v && $v->cliente_id ? \Illuminate\Support\Facades\DB::table('clientes')->where('id', $v->cliente_id)->value('nombre') : '—';
                }),
            Tables\Columns\TextColumn::make('local_destino_col')->label('Destino')->placeholder('—')
                ->state(fn (\App\Models\Despacho $d) => $d->local_destino_id ? \Illuminate\Support\Facades\DB::table('locales')->where('id', $d->local_destino_id)->value('nombre') : null),
            Tables\Columns\TextColumn::make('empleado_receptor_col')->label('Recibe')->placeholder('—')
                ->state(fn (\App\Models\Despacho $d) => $d->empleado_receptor_id ? \App\Models\User::find($d->empleado_receptor_id)?->name : null),
            Tables\Columns\TextColumn::make('cliente_col')->label('Cliente')
                ->state(function (\App\Models\Despacho $d) {
                    $p = \App\Models\PedidoEspecial::find($d->pedido_id);
                    return $p && $p->cliente_id ? (\Illuminate\Support\Facades\DB::table('clientes')->where('id', $p->cliente_id)->value('nombre') ?: '—') : '—';
                }),
            Tables\Columns\TextColumn::make('ruta')->label('Modalidad')->badge()
                ->formatStateUsing(fn ($state) => $state === 'transportista' ? 'Domicilio' : 'Retiro en local')
                ->color(fn ($state) => $state === 'transportista' ? 'info' : 'warning'),
            Tables\Columns\TextColumn::make('items_col')->label('Contenido')->wrap()
                ->state(function (\App\Models\Despacho $d) {
                    $its = \Illuminate\Support\Facades\DB::table('pedido_items')->where('pedido_id', $d->pedido_id)->get();
                    return $its->map(fn ($i) => $i->cantidad . '× ' . $i->nombre)->implode(', ') ?: '—';
                }),
            Tables\Columns\IconColumn::make('listo')->label('Listo')->boolean(),
            Tables\Columns\TextColumn::make('pago_estado')->label('Pago')->badge()
                ->state(fn (\App\Models\Despacho $d) => \App\Services\RecibosErp::saldo(\App\Models\PedidoEspecial::find($d->pedido_id) ?? new \App\Models\PedidoEspecial()) <= 0 ? 'Pagado' : 'Pendiente de pago')
                ->color(fn ($state) => $state === 'Pagado' ? 'success' : 'danger'),
            Tables\Columns\TextColumn::make('estado')->label('Proceso')->badge()
                ->formatStateUsing(fn ($state, $record) => match (true) {
                    $state === 'entregado' => 'Entregado',
                    $state === 'en_transito' => 'En tránsito',
                    $state === 'cancelado' => 'Cancelado',
                    $record->listo => 'Listo para despacho',
                    default => 'Programado',
                })
                ->color(fn ($state, $record) => match (true) {
                    $state === 'entregado' => 'success',
                    $state === 'en_transito' => 'info',
                    $state === 'cancelado' => 'danger',
                    $record->listo => 'warning',
                    default => 'gray',
                }),
        ])->defaultSort('id', 'desc')
        ->actions([
            Actions\Action::make('cobro')->label('Solicitar cobro')->icon('heroicon-o-banknotes')->color('danger')
                ->visible(function (\App\Models\Despacho $d) { $p = \App\Models\PedidoEspecial::find($d->pedido_id); return $p && ! in_array($p->estado_erp, ['anulado','cancelado'], true) && \App\Services\RecibosErp::saldo($p) > 0; })
                ->requiresConfirmation()->modalDescription('Notifica a vendedor, operaciones y contabilidad para cobrar el saldo.')
                ->action(fn (\App\Models\Despacho $d) => \App\Services\CobroSaldo::solicitar(\App\Models\PedidoEspecial::find($d->pedido_id))),
            Actions\Action::make('gestionar')->label('Gestionar')->icon('heroicon-o-clipboard-document-check')->color('primary')
                ->url(fn (\App\Models\Despacho $d) => static::getUrl('view', ['record' => $d->id])),
            Actions\Action::make('ver_pedido')->label('Ver pedido')->icon('heroicon-o-eye')->color('gray')
                ->visible(fn (\App\Models\Despacho $d) => (bool) $d->pedido_id)
                ->url(fn (\App\Models\Despacho $d) => \App\Filament\Resources\PedidoEspecialResource::getUrl('view', ['record' => $d->pedido_id]))
                ->openUrlInNewTab(),
            Actions\Action::make('ver_compra')->label('Ver compra')->icon('heroicon-o-eye')->color('gray')
                ->visible(fn (\App\Models\Despacho $d) => (bool) $d->compra_id)
                ->url(fn (\App\Models\Despacho $d) => \App\Filament\Resources\CompraResource::getUrl('view', ['record' => $d->compra_id]))
                ->openUrlInNewTab(),
            Actions\Action::make('ver_venta')->label('Ver venta')->icon('heroicon-o-eye')->color('gray')
                ->visible(fn (\App\Models\Despacho $d) => (bool) $d->venta_id)
                ->url(fn (\App\Models\Despacho $d) => \App\Filament\Resources\VentaResource::getUrl('view', ['record' => $d->venta_id]))
                ->openUrlInNewTab(),
            Actions\Action::make('cambiarFecha')->label('Cambiar fecha de despacho')->icon('heroicon-o-calendar')->color('warning')
                ->visible(fn (\App\Models\Despacho $d) => \App\Support\Acl::puedeAprobar() && $d->estado !== 'entregado')
                ->modalHeading('Cambiar fecha de despacho')
                ->modalDescription('Fecha en que el cliente quiere recibir el pedido. Se registra el cambio.')
                ->fillForm(fn (\App\Models\Despacho $d) => ['fecha_programada' => $d->fecha_programada])
                ->form([
                    \Filament\Forms\Components\DatePicker::make('fecha_programada')->label('Nueva fecha de despacho')->required()->minDate(now()->startOfDay()),
                    \Filament\Forms\Components\Textarea::make('motivo')->label('Motivo (opcional)')->rows(2),
                ])
                ->action(function (\App\Models\Despacho $record, array $data) {
                    $ant = $record->fecha_programada;
                    $record->update(['fecha_programada' => $data['fecha_programada']]);
                    if (class_exists(\App\Models\Bitacora::class)) {
                        \App\Models\Bitacora::registrar('cambió fecha de despacho', 'Despacho', $record->id, 'De ' . $ant . ' a ' . $data['fecha_programada'] . ($data['motivo'] ? ' · ' . $data['motivo'] : ''));
                    }
                    \Filament\Notifications\Notification::make()->success()->title('Fecha de despacho actualizada')->send();
                }),
            Actions\Action::make('confirmarRecepcionAbasto')->label('Confirmar recepción')->icon('heroicon-o-check-badge')->color('success')
                ->visible(fn (\App\Models\Despacho $d) => $d->tipo === 'abastecimiento' && $d->estado !== 'entregado'
                    && (Acl::puedeAprobar() || $d->empleado_receptor_id === auth()->id()))
                ->requiresConfirmation()
                ->modalHeading('Confirmar recepción de mercadería')
                ->modalDescription('Se sumará el stock al local destino y se marcará la compra/producción como recibida.')
                ->action(function (\App\Models\Despacho $d) {
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
                }),
        ])
        ->bulkActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDespacho::route('/'),
            'create' => Pages\CreateDespacho::route('/create'),
            'view'   => Pages\ViewDespacho::route('/{record}'),
            'edit'   => Pages\EditDespacho::route('/{record}/edit'),
        ];
    }
}
