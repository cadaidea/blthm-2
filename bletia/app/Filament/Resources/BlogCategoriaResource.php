<?php
namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;


use App\Support\Acl;
use App\Filament\Resources\BlogCategoriaResource\Pages;
use App\Models\BlogCategoria;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BlogCategoriaResource extends Resource
{
    protected static ?string $model = BlogCategoria::class;


    public static function canViewAny(): bool
    {
        return Acl::ve(static::class);
    }
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return \App\Models\BlogCategoria::query();
    }
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';
    protected static string|\UnitEnum|null $navigationGroup = 'Blog';
    protected static ?string $modelLabel = 'Categoría de blog';
    protected static ?string $pluralModelLabel = 'Categorías de blog';
    protected static ?int $navigationSort = 2;

    public static function canDeleteAny(): bool { return \App\Support\Acl::esAdmin(); }
    public static function canDelete($record): bool { return \App\Support\Acl::esAdmin(); }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('nombre')->required()->maxLength(120),
            Forms\Components\TextInput::make('slug')->maxLength(140)->helperText('Se genera solo.'),
            Forms\Components\TextInput::make('orden')->numeric()->default(0),
            Forms\Components\Toggle::make('activo')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('nombre')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('articulos_count')->counts('articulos')->label('Artículos'),
            Tables\Columns\IconColumn::make('activo')->boolean(),
            Tables\Columns\TextColumn::make('orden')->sortable(),
        ])->reorderable('orden')->defaultSort('orden')
          ->actions([Actions\EditAction::make()])
          ->bulkActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBlogCategoria::route('/'),
            'create' => Pages\CreateBlogCategoria::route('/create'),
            'edit'   => Pages\EditBlogCategoria::route('/{record}/edit'),
        ];
    }
}
