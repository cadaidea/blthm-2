<?php
namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;

use App\Support\Acl;
use App\Filament\Resources\WooPedidoResource\Pages;
use App\Models\WooPedido;
use Filament\Resources\Resource;
use Filament\Tables; use Filament\Tables\Table;
use Filament\Infolists; use Filament\Infolists\Infolist;
class WooPedidoResource extends Resource {
    protected static ?string $model = WooPedido::class;


    public static function canViewAny(): bool
    {
        return Acl::ve(static::class);
    }
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return \App\Models\WooPedido::query();
    }
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';
    protected static string|\UnitEnum|null $navigationGroup = 'Ventas';
    protected static ?string $modelLabel = 'Pedido online (Woo)';
    protected static ?string $pluralModelLabel = 'Pedidos online (Woo)';
    protected static ?int $navigationSort = 4;
    public static function table(Table $table): Table {
        return $table->columns([
            Tables\Columns\TextColumn::make('numero')->label('N°')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('cliente_nombre')->label('Cliente')->searchable(),
            Tables\Columns\TextColumn::make('cliente_email')->label('Email')->searchable(),
            Tables\Columns\TextColumn::make('estado')->badge(),
            Tables\Columns\TextColumn::make('total')->money('usd'),
            Tables\Columns\TextColumn::make('fecha')->dateTime('d/m/Y H:i')->sortable(),
        ])->defaultSort('fecha', 'desc')
          ->filters([Tables\Filters\SelectFilter::make('estado')->options([
              'processing' => 'processing', 'completed' => 'completed', 'pending' => 'pending', 'cancelled' => 'cancelled', 'on-hold' => 'on-hold', 'refunded' => 'refunded', 'failed' => 'failed',
          ])])
          ->actions([Actions\ViewAction::make()]);
    }
    public static function infolist(Schema $schema): Schema {
        return $schema->schema([
            Infolists\Components\TextEntry::make('numero')->label('N°'),
            Infolists\Components\TextEntry::make('cliente_nombre')->label('Cliente'),
            Infolists\Components\TextEntry::make('cliente_email')->label('Email'),
            Infolists\Components\TextEntry::make('estado'),
            Infolists\Components\TextEntry::make('total')->money('usd'),
            Infolists\Components\RepeatableEntry::make('items')->label('Productos')->schema([
                Infolists\Components\TextEntry::make('producto_nombre')->label('Producto'),
                Infolists\Components\TextEntry::make('cantidad'),
                Infolists\Components\TextEntry::make('total')->money('usd'),
                Infolists\Components\TextEntry::make('variaciones')->label('Variables')->placeholder('—'),
            ])->columns(4),
        ]);
    }
    public static function getRelations(): array { return []; }
    public static function getPages(): array {
        return ['index' => Pages\ListWooPedido::route('/'), 'view' => Pages\ViewWooPedido::route('/{record}')];
    }
}
