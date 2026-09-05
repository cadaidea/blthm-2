<?php
namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;


use App\Support\Acl;
use App\Filament\Resources\EtiquetaResource\Pages;
use App\Models\Etiqueta;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EtiquetaResource extends Resource
{
    protected static ?string $model = Etiqueta::class;


    public static function canViewAny(): bool
    {
        return Acl::ve(static::class);
    }
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return \App\Models\Etiqueta::query();
    }
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-hashtag';
    protected static string|\UnitEnum|null $navigationGroup = 'Blog';
    protected static ?string $modelLabel = 'Etiqueta';
    protected static ?string $pluralModelLabel = 'Etiquetas';
    protected static ?int $navigationSort = 3;

    public static function canDeleteAny(): bool { return \App\Support\Acl::esAdmin(); }
    public static function canDelete($record): bool { return \App\Support\Acl::esAdmin(); }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('nombre')->required(),
            Forms\Components\TextInput::make('slug')->helperText('Se genera solo.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('nombre')->searchable(),
            Tables\Columns\TextColumn::make('articulos_count')->counts('articulos')->label('Artículos'),
        ])->actions([Actions\EditAction::make()])
          ->bulkActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListEtiqueta::route('/'), 'create' => Pages\CreateEtiqueta::route('/create'), 'edit' => Pages\EditEtiqueta::route('/{record}/edit')];
    }
}
