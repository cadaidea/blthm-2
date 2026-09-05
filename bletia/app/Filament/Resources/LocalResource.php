<?php
namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;

use App\Support\Acl;
use App\Filament\Resources\LocalResource\Pages;
use App\Models\Local;
use Filament\Forms; use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables; use Filament\Tables\Table;
class LocalResource extends Resource {
    protected static ?string $model = Local::class;


    public static function canViewAny(): bool
    {
        return Acl::ve(static::class);
    }
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return \App\Models\Local::query();
    }
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';
    protected static string|\UnitEnum|null $navigationGroup = 'Logística';
    protected static ?string $modelLabel = 'Local / Bodega';
    protected static ?string $pluralModelLabel = 'Locales y bodegas';
    protected static ?int $navigationSort = 3;
    public static function form(Schema $schema): Schema {
        return $schema->schema([
            Forms\Components\TextInput::make('nombre')->required(),
            Forms\Components\TextInput::make('direccion')->label('Dirección'),
            Forms\Components\TextInput::make('ciudad')->label('Ciudad'),
            Forms\Components\Select::make('tipo')->options([
                'local_venta' => 'Local de venta', 'bodega_stock' => 'Bodega de stock', 'bodega_pedidos' => 'Bodega de pedidos',
            ])->default('local_venta')->required(),
            Forms\Components\Toggle::make('activo')->default(true),
        ])->columns(2);
    }
    public static function table(Table $table): Table {
        return $table->columns([
            Tables\Columns\TextColumn::make('nombre')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('tipo')->badge()->formatStateUsing(fn ($state) => match ($state) {
                'bodega_stock' => 'Bodega stock', 'bodega_pedidos' => 'Bodega pedidos', default => 'Local venta',
            }),
            Tables\Columns\IconColumn::make('activo')->boolean(),
        ])->filters([Tables\Filters\TernaryFilter::make('activo')])
          ->actions([Actions\EditAction::make(), Actions\DeleteAction::make()])
          ->bulkActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])]);
    }
    public static function getPages(): array {
        return ['index' => Pages\ListLocal::route('/'), 'create' => Pages\CreateLocal::route('/create'), 'edit' => Pages\EditLocal::route('/{record}/edit')];
    }
}
