<?php
namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;


use App\Support\Acl;
use App\Filament\Resources\AtributoResource\Pages;
use App\Models\Atributo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AtributoResource extends Resource
{
    protected static ?string $model = Atributo::class;


    public static function canViewAny(): bool
    {
        return Acl::ve(static::class);
    }
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return \App\Models\Atributo::query();
    }
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-swatch';
    protected static string|\UnitEnum|null $navigationGroup = 'Catálogo';
    protected static ?string $modelLabel = 'Variable de producto';
    protected static ?string $pluralModelLabel = 'Variables de producto';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('nombre')->required()->datalist(['Tapiz', 'Lacado', 'Lado'])
                ->helperText('Ej.: Tapiz, Lacado, Lado.'),
            Forms\Components\Select::make('tipo')->options(['color' => 'Color', 'imagen' => 'Imagen', 'texto' => 'Texto'])->default('color')->required(),
            Forms\Components\Repeater::make('opciones')->relationship()->label('Opciones')->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('valor')->required()->placeholder('Lino beige / Left / Nogal'),
                    Forms\Components\ColorPicker::make('color'),
                    Forms\Components\FileUpload::make('imagen')->image()->directory('atributos')->disk('public'),
                ])->orderColumn('orden')->defaultItems(1)
                ->itemLabel(fn (array $state) => $state['valor'] ?? null),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('nombre')->searchable(),
            Tables\Columns\TextColumn::make('tipo')->badge(),
            Tables\Columns\TextColumn::make('opciones_count')->counts('opciones')->label('Opciones'),
        ])->reorderable('orden')->defaultSort('orden')
          ->actions([Actions\EditAction::make()])
          ->bulkActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListAtributo::route('/'), 'create' => Pages\CreateAtributo::route('/create'), 'edit' => Pages\EditAtributo::route('/{record}/edit')];
    }
}
