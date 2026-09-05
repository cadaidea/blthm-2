<?php
namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;

use App\Support\Acl;
use App\Filament\Resources\CuentaResource\Pages;
use App\Models\Cuenta;
use Filament\Forms; use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables; use Filament\Tables\Table;

class CuentaResource extends Resource
{
    protected static ?string $model = Cuenta::class;

    public static function canViewAny(): bool { return Acl::puedeContabilidad(); }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-list-bullet';
    protected static string|\UnitEnum|null $navigationGroup = 'Contabilidad';
    protected static ?string $modelLabel = 'Cuenta contable';
    protected static ?string $pluralModelLabel = 'Plan de cuentas';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('codigo')->label('Código')->required()->unique(ignoreRecord: true)
                ->helperText('Jerárquico, ej: 1.1.01.01'),
            Forms\Components\TextInput::make('nombre')->required(),
            Forms\Components\Select::make('tipo')->required()->options([
                'activo' => 'Activo', 'pasivo' => 'Pasivo', 'patrimonio' => 'Patrimonio',
                'ingreso' => 'Ingreso', 'gasto' => 'Gasto', 'costo' => 'Costo',
            ])->native(false),
            Forms\Components\Select::make('naturaleza')->required()->options([
                'deudora' => 'Deudora (suma al Debe)', 'acreedora' => 'Acreedora (suma al Haber)',
            ])->native(false),
            Forms\Components\Select::make('padre_id')->label('Cuenta padre')
                ->options(fn () => Cuenta::orderBy('codigo')->get()->mapWithKeys(fn ($c) => [$c->id => $c->codigo . ' · ' . $c->nombre]))
                ->searchable(),
            Forms\Components\Toggle::make('es_movimiento')->label('Recibe asientos (cuenta de movimiento)')->default(true)
                ->helperText('Apágalo si es una cuenta de grupo/título que no recibe montos directos.'),
            Forms\Components\Toggle::make('activo')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('codigo')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('nombre')->searchable()->wrap(),
            Tables\Columns\TextColumn::make('tipo')->badge(),
            Tables\Columns\IconColumn::make('es_movimiento')->label('Mov.')->boolean(),
            Tables\Columns\IconColumn::make('activo')->boolean(),
        ])
        ->defaultSort('codigo')
        ->paginated([50, 100, 'all'])
        ->filters([
            Tables\Filters\SelectFilter::make('tipo')->options([
                'activo' => 'Activo', 'pasivo' => 'Pasivo', 'patrimonio' => 'Patrimonio',
                'ingreso' => 'Ingreso', 'gasto' => 'Gasto', 'costo' => 'Costo',
            ]),
        ])
        ->actions([Actions\EditAction::make()])
        ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCuentas::route('/'),
            'create' => Pages\CreateCuenta::route('/create'),
            'edit'   => Pages\EditCuenta::route('/{record}/edit'),
        ];
    }
}
