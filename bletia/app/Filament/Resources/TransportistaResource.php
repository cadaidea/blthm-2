<?php
namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;

use App\Support\Acl;
use App\Filament\Resources\TransportistaResource\Pages;
use App\Models\Transportista;
use Filament\Forms; use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables; use Filament\Tables\Table;
class TransportistaResource extends Resource {
    protected static ?string $model = Transportista::class;


    public static function canViewAny(): bool
    {
        return Acl::ve(static::class);
    }
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return \App\Models\Transportista::query();
    }
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-truck';
    protected static string|\UnitEnum|null $navigationGroup = 'Logística';
    protected static ?string $modelLabel = 'Transportista';
    protected static ?string $pluralModelLabel = 'Transportistas';
    protected static ?int $navigationSort = 2;
    public static function form(Schema $schema): Schema {
        return $schema->schema([
            Forms\Components\TextInput::make('nombre')->label('Empresa / Nombre')->required()->columnSpanFull(),
            Forms\Components\Select::make('tipo_identificacion')->label('Tipo de identificación')
                ->options(['ruc' => 'RUC', 'nui' => 'NUI / Cédula', 'pasaporte' => 'Pasaporte'])->default('ruc'),
            Forms\Components\TextInput::make('identificacion')->label('RUC / NUI'),
            Forms\Components\TextInput::make('email')->email(),
            Forms\Components\TextInput::make('celular')->label('Celular')->tel(),
            Forms\Components\TextInput::make('celular2')->label('Celular secundario (opcional)')->tel(),
            Forms\Components\TextInput::make('direccion')->label('Dirección')->columnSpanFull(),
            Forms\Components\Toggle::make('activo')->default(true),
        ])->columns(2);
    }
    public static function table(Table $table): Table {
        return $table->columns([
            Tables\Columns\TextColumn::make('nombre')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('identificacion')->label('RUC/NUI')->placeholder('—'),
            Tables\Columns\TextColumn::make('celular')->placeholder('—'),
            Tables\Columns\IconColumn::make('activo')->boolean(),
        ])->filters([Tables\Filters\TernaryFilter::make('activo')])
          ->actions([Actions\EditAction::make()])
          ->bulkActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])]);
    }
    public static function getPages(): array {
        return ['index' => Pages\ListTransportista::route('/'), 'create' => Pages\CreateTransportista::route('/create'), 'edit' => Pages\EditTransportista::route('/{record}/edit')];
    }
}
