<?php
namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;

use App\Support\Acl;
use App\Filament\Resources\ProveedorResource\Pages;
use App\Models\Proveedor;
use Filament\Forms; use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables; use Filament\Tables\Table;
class ProveedorResource extends Resource {
    protected static ?string $model = Proveedor::class;


    public static function canViewAny(): bool
    {
        return Acl::ve(static::class);
    }
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return \App\Models\Proveedor::query();
    }
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';
    protected static string|\UnitEnum|null $navigationGroup = 'Compras';
    protected static ?string $modelLabel = 'Proveedor';
    protected static ?string $pluralModelLabel = 'Proveedores';
    protected static ?int $navigationSort = 2;
    public static function form(Schema $schema): Schema {
        return $schema->schema([
            Forms\Components\TextInput::make('nombre')->required()->columnSpanFull(),
            Forms\Components\Select::make('tipo_identificacion')->label('Tipo de identificación')
                ->options(['ruc' => 'RUC', 'cedula' => 'Cédula', 'pasaporte' => 'Pasaporte'])
                ->live()->native(false),
            Forms\Components\TextInput::make('identificacion')->label('N° de identificación')
                ->maxLength(20)
                ->helperText('Cédula: 10 dígitos · RUC: 13 dígitos. Necesario para el ATS y bancarización.')
                ->rule(fn ($get) => function ($attr, $value, $fail) use ($get) {
                    if (! $value) return;
                    $tipo = $get('tipo_identificacion');
                    if ($tipo === 'cedula' && ! preg_match('/^[0-9]{10}$/', $value)) $fail('La cédula debe tener 10 dígitos.');
                    if ($tipo === 'ruc' && ! preg_match('/^[0-9]{13}$/', $value)) $fail('El RUC debe tener 13 dígitos.');
                }),
            Forms\Components\TextInput::make('direccion')->label('Dirección'),
            Forms\Components\TextInput::make('contacto')->label('Persona de contacto'),
            Forms\Components\TextInput::make('email')->email(),
            Forms\Components\TextInput::make('telefono')->label('Teléfono')->tel(),
            Forms\Components\TextInput::make('ciudad'),
            Forms\Components\Toggle::make('activo')->default(true),
            Forms\Components\Textarea::make('notas')->rows(2)->columnSpanFull(),
        ])->columns(2);
    }
    public static function table(Table $table): Table {
        return $table->columns([
            Tables\Columns\TextColumn::make('nombre')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('identificacion')->label('Identificación')->searchable()->toggleable(),
            Tables\Columns\TextColumn::make('contacto')->label('Contacto'),
            Tables\Columns\TextColumn::make('telefono')->label('Teléfono'),
            Tables\Columns\TextColumn::make('ciudad'),
            Tables\Columns\IconColumn::make('activo')->boolean(),
        ])->filters([Tables\Filters\TernaryFilter::make('activo')])
          ->actions([Actions\EditAction::make()])
          ->bulkActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])]);
    }
    public static function getPages(): array {
        return ['index' => Pages\ListProveedor::route('/'), 'create' => Pages\CreateProveedor::route('/create'), 'edit' => Pages\EditProveedor::route('/{record}/edit')];
    }
}
