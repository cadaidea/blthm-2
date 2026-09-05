<?php
namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;

use App\Filament\Resources\CuponResource\Pages;
use App\Models\Cupon;
use App\Support\Acl;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CuponResource extends Resource
{
    protected static ?string $model = Cupon::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationLabel = 'Cupones';
    protected static ?string $modelLabel = 'Cupón';
    protected static ?string $pluralModelLabel = 'Cupones';
    protected static string|\UnitEnum|null $navigationGroup = 'Marketing';

    protected static function permitido(): bool { return Acl::esAdmin() || Acl::esOperaciones(); }
    public static function shouldRegisterNavigation(): bool { return static::permitido(); }
    public static function canViewAny(): bool { return static::permitido(); }

    public static function canDeleteAny(): bool { return \App\Support\Acl::esAdmin(); }
    public static function canDelete($record): bool { return \App\Support\Acl::esAdmin(); }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('codigo')->label('Código')->required()->unique(ignoreRecord: true)
                ->dehydrateStateUsing(fn ($state) => strtoupper(trim((string) $state)))->placeholder('BIENVENIDA10'),
            Forms\Components\Select::make('tipo')->options(['porcentaje' => 'Porcentaje (%)', 'fijo' => 'Monto fijo ($)'])
                ->default('porcentaje')->required()->native(false)->live(),
            Forms\Components\TextInput::make('valor')->numeric()->required()
                ->suffix(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tipo') === 'porcentaje' ? '%' : 'USD'),
            Forms\Components\Select::make('audiencia')->options([
                'primera_compra' => 'Solo primera compra (captar)',
                'recurrente'     => 'Clientes que ya compraron (fidelizar)',
                'todos'          => 'Todos',
            ])->default('primera_compra')->required()->native(false),
            Forms\Components\TextInput::make('minimo_compra')->label('Mínimo de compra (USD)')->numeric()->nullable(),
            Forms\Components\TextInput::make('limite_global')->label('Límite total de usos (opcional)')->numeric()->nullable(),
            Forms\Components\DatePicker::make('vence_at')->label('Vence el (opcional)'),
            Forms\Components\Toggle::make('activo')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('codigo')->searchable()->weight('bold')->copyable(),
            Tables\Columns\TextColumn::make('valor')->formatStateUsing(fn ($state, $record) => $record->tipo === 'porcentaje' ? $state.'%' : '$'.number_format($state, 2)),
            Tables\Columns\TextColumn::make('audiencia')->badge()->formatStateUsing(fn ($state) => match ($state) {
                'primera_compra' => 'Primera compra', 'recurrente' => 'Fidelización', default => 'Todos',
            }),
            Tables\Columns\TextColumn::make('usos')->label('Usos'),
            Tables\Columns\TextColumn::make('vence_at')->date('d/m/Y')->placeholder('—'),
            Tables\Columns\IconColumn::make('activo')->boolean(),
        ])->actions([Actions\EditAction::make(), Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCupones::route('/'),
            'create' => Pages\CreateCupon::route('/crear'),
            'edit'   => Pages\EditCupon::route('/{record}/editar'),
        ];
    }
}
