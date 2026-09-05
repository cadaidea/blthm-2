<?php
namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;

use App\Support\Acl;
use App\Filament\Resources\ParametroLaboralResource\Pages;
use App\Models\ParametroLaboral;
use Filament\Forms; use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables; use Filament\Tables\Table;

class ParametroLaboralResource extends Resource
{
    protected static ?string $model = ParametroLaboral::class;
    public static function canViewAny(): bool { return Acl::esAdmin(); }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static string|\UnitEnum|null $navigationGroup = 'Nómina / RRHH';
    protected static ?string $modelLabel = 'Parámetro laboral';
    protected static ?string $pluralModelLabel = 'Parámetros (SBU / IESS)';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('anio')->label('Año')->numeric()->required()->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('sbu')->label('SBU (salario básico)')->numeric()->prefix('$')->required(),
            Forms\Components\TextInput::make('aporte_personal')->label('Aporte personal IESS')->numeric()->suffix('%')->required()->default(9.45),
            Forms\Components\TextInput::make('aporte_patronal')->label('Aporte patronal IESS')->numeric()->suffix('%')->required()->default(11.15),
            Forms\Components\TextInput::make('fondos_reserva')->label('Fondos de reserva')->numeric()->suffix('%')->required()->default(8.33),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('anio')->sortable(),
            Tables\Columns\TextColumn::make('sbu')->money('usd'),
            Tables\Columns\TextColumn::make('aporte_personal')->suffix('%'),
            Tables\Columns\TextColumn::make('aporte_patronal')->suffix('%'),
            Tables\Columns\TextColumn::make('fondos_reserva')->suffix('%'),
        ])->defaultSort('anio', 'desc')
        ->actions([Actions\EditAction::make()])->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListParametrosLaborales::route('/'),
            'create' => Pages\CreateParametroLaboral::route('/create'),
            'edit'   => Pages\EditParametroLaboral::route('/{record}/edit'),
        ];
    }
}
