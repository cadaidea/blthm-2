<?php

namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;


use App\Support\Acl;
use App\Filament\Resources\PedidoResource\Pages;
use App\Models\Pedido;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Local;
use App\Models\MovimientoStock;

class PedidoResource extends Resource
{
    protected static ?string $model = Pedido::class;


    public static function canViewAny(): bool
    {
        return Acl::ve(static::class);
    }
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return \App\Models\Pedido::query()->with(['cliente']);
    }
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';
    protected static string|\UnitEnum|null $navigationGroup = 'Ventas';
    protected static ?string $modelLabel = 'Pedido';
    protected static ?string $pluralModelLabel = 'Pedidos';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('codigo')->disabled(),
            Forms\Components\TextInput::make('estado')->disabled(),
            Forms\Components\TextInput::make('total')->prefix('$')->disabled(),
            Forms\Components\TextInput::make('email')->disabled(),
            Forms\Components\TextInput::make('pp_transaction_id')->label('PayPhone Tx')->disabled(),
            Forms\Components\Repeater::make('items')->relationship()->label('Productos')->disabled()
                ->columns(3)->schema([
                    Forms\Components\TextInput::make('nombre'),
                    Forms\Components\TextInput::make('variantes'),
                    Forms\Components\TextInput::make('cantidad'),
                    Forms\Components\TextInput::make('subtotal')->prefix('$'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('codigo')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('cliente.nombre')->label('Cliente')->searchable(),
                Tables\Columns\TextColumn::make('total')->money('USD')->sortable(),
                Tables\Columns\TextColumn::make('estado')->badge()->color(fn (string $state) => match ($state) {
                    'pagado' => 'success', 'despachado' => 'info', 'rechazado' => 'danger', default => 'warning',
                }),
                Tables\Columns\TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')->options([
                    'pendiente_pago' => 'Pendiente', 'pagado' => 'Pagado', 'despachado' => 'Despachado', 'rechazado' => 'Rechazado',
                ]),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\Action::make('despachar')
                    ->label('Despachar')->icon('heroicon-o-truck')->color('info')
                    ->visible(fn (\App\Models\Pedido $r) => $r->estado === 'pagado')
                    ->form([
                        Forms\Components\Select::make('bodega_despacho_id')->label('Bodega de salida')
                            ->options(Local::pluck('nombre', 'id'))->required(),
                    ])
                    ->action(function (\App\Models\Pedido $r, array $data) {
                        $bod = (int) $data['bodega_despacho_id'];
                        foreach ($r->items as $it) {
                            if ($it->producto_id) {
                                MovimientoStock::create([
                                    'producto_id' => $it->producto_id, 'local_id' => $bod,
                                    'tipo' => 'salida', 'cantidad' => $it->cantidad,
                                    'referencia' => 'Pedido ' . $r->codigo, 'nota' => 'Despacho',
                                ]);
                            }
                        }
                        $r->update(['estado' => 'despachado', 'bodega_despacho_id' => $bod, 'despachado_at' => now()]);
                        \Filament\Notifications\Notification::make()->success()->title('Pedido despachado')->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPedidos::route('/'),
            'view'  => Pages\ViewPedido::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
