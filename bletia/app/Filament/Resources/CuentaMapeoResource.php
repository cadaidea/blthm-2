<?php
namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;

use App\Support\Acl;
use App\Filament\Resources\CuentaMapeoResource\Pages;
use App\Models\CuentaMapeo;
use App\Models\Cuenta;
use App\Services\ContabilidadAuto;
use Filament\Forms; use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables; use Filament\Tables\Table;

class CuentaMapeoResource extends Resource
{
    protected static ?string $model = CuentaMapeo::class;

    public static function canViewAny(): bool { return Acl::puedeContabilidad(); }
    public static function canCreate(): bool { return false; } // set fijo, solo se edita la cuenta

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-link';
    protected static string|\UnitEnum|null $navigationGroup = 'Contabilidad';
    protected static ?string $modelLabel = 'Mapeo contable';
    protected static ?string $pluralModelLabel = 'Mapeos (evento → cuenta)';
    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('clave')->disabled(),
            Forms\Components\TextInput::make('descripcion')->disabled(),
            Forms\Components\Select::make('codigo_cuenta')->label('Cuenta contable')->required()
                ->options(fn () => Cuenta::where('es_movimiento', true)->orderBy('codigo')
                    ->get()->mapWithKeys(fn ($c) => [$c->codigo => $c->codigo . ' · ' . $c->nombre]))
                ->searchable()
                ->helperText('A qué cuenta del plan va este evento. Cámbiala si tu contador lo indica.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('clave')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('descripcion')->wrap(),
            Tables\Columns\TextColumn::make('codigo_cuenta')->label('Cuenta')->badge(),
        ])->defaultSort('clave')
        ->actions([Actions\EditAction::make()])
        ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCuentaMapeos::route('/'),
            'edit'  => Pages\EditCuentaMapeo::route('/{record}/edit'),
        ];
    }
}
