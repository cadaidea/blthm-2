<?php
namespace App\Filament\Resources;
use Filament\Schemas\Schema;


use App\Support\Acl;
use App\Filament\Resources\MovimientoStockResource\Pages;
use App\Models\MovimientoStock;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MovimientoStockResource extends Resource
{
    protected static ?string $model = MovimientoStock::class;


    public static function canViewAny(): bool
    {
        return Acl::ve(static::class);
    }
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return \App\Models\MovimientoStock::query()->with(['local', 'producto']);
    }
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static string|\UnitEnum|null $navigationGroup = 'Inventario';
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = 'Movimiento de stock';
    protected static ?string $pluralModelLabel = 'Movimientos de stock';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('producto_id')->label('Producto')
                ->relationship('producto', 'nombre')->searchable()->preload()->required(),
            Forms\Components\Select::make('tipo')->required()->live()->options([
                'entrada' => 'Entrada (+)',
                'salida' => 'Salida (−)',
                'ajuste' => 'Ajuste (fijar cantidad)',
                'transferencia' => 'Transferencia entre bodegas',
            ]),
            Forms\Components\Select::make('local_id')->label('Ubicación / Bodega origen')
                ->relationship('local', 'nombre')->required()
                ->helperText(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tipo') === 'transferencia' ? 'Bodega de la que sale el stock.' : null),
            Forms\Components\Select::make('local_destino_id')->label('Bodega destino (solo si es transferencia)')
                ->relationship('localDestino', 'nombre')
                ->helperText('Déjalo vacío si no es una transferencia entre bodegas.'),
            Forms\Components\TextInput::make('cantidad')->numeric()->minValue(0)->required(),
            Forms\Components\TextInput::make('referencia')->maxLength(191),
            Forms\Components\TextInput::make('nota')->maxLength(191),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
            Tables\Columns\TextColumn::make('producto.nombre')->label('Producto')->searchable()->limit(40),
            Tables\Columns\TextColumn::make('tipo')->badge()->color(fn ($state) => match ($state) {
                'entrada' => 'success', 'salida' => 'danger', 'transferencia' => 'info', default => 'warning',
            }),
            Tables\Columns\TextColumn::make('local.nombre')->label('Ubicación'),
            Tables\Columns\TextColumn::make('localDestino.nombre')->label('Destino')->placeholder('—'),
            Tables\Columns\TextColumn::make('cantidad'),
        ])->defaultSort('created_at', 'desc')
          ->filters([Tables\Filters\SelectFilter::make('tipo')->options([
                'entrada' => 'Entrada', 'salida' => 'Salida', 'ajuste' => 'Ajuste', 'transferencia' => 'Transferencia',
          ])]);
    }

    public static function canEdit($record): bool { return false; }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListMovimientoStock::route('/'),
            'create' => Pages\CreateMovimientoStock::route('/create'),
        ];
    }
}
