<?php
namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;

use App\Support\Acl;
use App\Support\Impuestos;
use App\Filament\Resources\ImpuestoResource\Pages;
use App\Models\Impuesto;
use Filament\Forms; use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables; use Filament\Tables\Table;

class ImpuestoResource extends Resource
{
    protected static ?string $model = Impuesto::class;

    public static function canViewAny(): bool { return Acl::puedeContabilidad(); }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';
    protected static string|\UnitEnum|null $navigationGroup = 'Contabilidad';
    protected static ?string $modelLabel = 'Impuesto';
    protected static ?string $pluralModelLabel = 'Impuestos';
    protected static ?int $navigationSort = 9;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('nombre')->required()->default('IVA')->columnSpanFull(),
            Forms\Components\Select::make('tipo')->required()->default('iva')
                ->options([
                    'iva' => 'IVA',
                    'retencion_iva' => 'Retención IVA',
                    'retencion_renta' => 'Retención Renta',
                    'ice' => 'ICE',
                ])->native(false),
            Forms\Components\TextInput::make('porcentaje')->numeric()->required()->suffix('%')
                ->minValue(0)->maxValue(100),
            Forms\Components\TextInput::make('codigo_sri')->label('Código SRI')->maxLength(10),
            Forms\Components\Toggle::make('activo')->default(true),
            Forms\Components\DatePicker::make('vigente_desde')->label('Vigente desde')->required()->default(now()),
            Forms\Components\DatePicker::make('vigente_hasta')->label('Vigente hasta')
                ->helperText('Déjalo vacío si es la tarifa actual (sin fecha de fin).'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('nombre')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('tipo')->badge()->formatStateUsing(fn ($state) => match ($state) {
                'iva' => 'IVA', 'retencion_iva' => 'Ret. IVA', 'retencion_renta' => 'Ret. Renta', 'ice' => 'ICE', default => $state,
            }),
            Tables\Columns\TextColumn::make('porcentaje')->suffix('%')->sortable(),
            Tables\Columns\TextColumn::make('vigente_desde')->date('d/m/Y')->label('Desde'),
            Tables\Columns\TextColumn::make('vigente_hasta')->date('d/m/Y')->label('Hasta')->placeholder('Vigente'),
            Tables\Columns\IconColumn::make('activo')->boolean(),
        ])
        ->defaultSort('vigente_desde', 'desc')
        ->filters([Tables\Filters\SelectFilter::make('tipo')->options([
            'iva' => 'IVA', 'retencion_iva' => 'Ret. IVA', 'retencion_renta' => 'Ret. Renta', 'ice' => 'ICE',
        ])])
        ->actions([Actions\EditAction::make()])
        ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListImpuestos::route('/'),
            'create' => Pages\CreateImpuesto::route('/create'),
            'edit'   => Pages\EditImpuesto::route('/{record}/edit'),
        ];
    }
}
