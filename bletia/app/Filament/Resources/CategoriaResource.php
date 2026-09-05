<?php

namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;


use App\Support\Acl;
use App\Filament\Resources\CategoriaResource\Pages;
use App\Models\Categoria;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CategoriaResource extends Resource
{
    protected static ?string $model = Categoria::class;


    public static function canViewAny(): bool
    {
        return Acl::ve(static::class);
    }
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return \App\Models\Categoria::query()->with(['parent']);
    }
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static string|\UnitEnum|null $navigationGroup = 'Catálogo';
    protected static ?string $modelLabel = 'Categoría';
    protected static ?string $pluralModelLabel = 'Categorías';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('nombre')->required()->maxLength(191)
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, \Filament\Schemas\Components\Utilities\Set $set) => $set('slug', \Illuminate\Support\Str::slug((string) $state))),
            Forms\Components\TextInput::make('slug')->maxLength(191),
            Forms\Components\Select::make('parent_id')->label('Categoría padre')
                ->relationship('parent', 'nombre')->searchable()->preload(),
            Forms\Components\Textarea::make('descripcion')->rows(3)->columnSpanFull(),
            Forms\Components\FileUpload::make('imagen')->label('Imagen de categoría (se muestra en la home)')
                ->directory('categorias')->disk('public')
                ->acceptedFileTypes(['image/svg+xml', 'image/png', 'image/jpeg', 'image/webp'])
                ->saveUploadedFileUsing(\App\Support\WebpUpload::handler())
                ->columnSpanFull(),
            Forms\Components\TextInput::make('orden')->numeric()->default(0),
            Forms\Components\Toggle::make('activo')->default(true),
            Forms\Components\TextInput::make('meta_title')->label('Meta título')->maxLength(70)->columnSpanFull(),
            Forms\Components\Textarea::make('meta_description')->label('Meta descripción')->rows(2)->maxLength(320)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('imagen')->label('')->disk('public')->square(),
                Tables\Columns\TextColumn::make('nombre')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('parent.nombre')->label('Padre'),
                Tables\Columns\TextColumn::make('productos_count')->counts('productos')->label('Productos'),
                Tables\Columns\IconColumn::make('activo')->boolean(),
                Tables\Columns\TextColumn::make('orden')->sortable(),
            ])
            ->reorderable('orden')
            ->actions([Actions\EditAction::make()])
            ->bulkActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])])
            ->defaultSort('orden');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCategorias::route('/'),
            'create' => Pages\CreateCategoria::route('/create'),
            'edit'   => Pages\EditCategoria::route('/{record}/edit'),
        ];
    }
}
