<?php
namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;



use App\Forms\Components\EditorJsField;
use App\Support\Acl;
use App\Filament\Resources\ArticuloResource\Pages;
use App\Filament\Support\Bloques;
use App\Models\Articulo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ArticuloResource extends Resource
{
    protected static ?string $model = Articulo::class;


    public static function canViewAny(): bool
    {
        return Acl::ve(static::class);
    }
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return \App\Models\Articulo::query()->with(['categoria']);
    }
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-newspaper';
    protected static string|\UnitEnum|null $navigationGroup = 'Blog';
    protected static ?string $modelLabel = 'Artículo';
    protected static ?string $pluralModelLabel = 'Artículos';
    protected static ?int $navigationSort = 1;

    public static function canDeleteAny(): bool { return \App\Support\Acl::esAdmin(); }
    public static function canDelete($record): bool { return \App\Support\Acl::esAdmin(); }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Tabs::make('Articulo')->columnSpanFull()->tabs([

                \Filament\Schemas\Components\Tabs\Tab::make('Contenido')->schema([
                    Forms\Components\TextInput::make('titulo')->required()->maxLength(191)->columnSpanFull()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, \Filament\Schemas\Components\Utilities\Set $set) => $set('slug', \Illuminate\Support\Str::slug((string) $state))),
                    Forms\Components\TextInput::make('slug')->maxLength(191)->helperText('Se genera solo.')->columnSpanFull(),
                    Forms\Components\Textarea::make('extracto')->label('Entradilla')->rows(2)->columnSpanFull(),
                    EditorJsField::make('contenido_json')->label('Contenido')->columnSpanFull(),
                    Forms\Components\Builder::make('bloques')->label('Contenido por bloques (recomendado)')->columnSpanFull()
                        ->collapsible()->cloneable()->blockNumbers(false)
                        ->blocks(Bloques::schema()),
                ]),

                \Filament\Schemas\Components\Tabs\Tab::make('Portada')->schema([
                    Forms\Components\FileUpload::make('imagen')->image()->directory('blog')->disk('public')->imageEditor()->saveUploadedFileUsing(\App\Support\WebpUpload::handler())->columnSpanFull(),
                    Forms\Components\Toggle::make('imagen_cabecera')->label('Usar la imagen como cabecera (hero a pantalla completa, header transparente)')->default(false)->columnSpanFull(),
                ]),

                \Filament\Schemas\Components\Tabs\Tab::make('Clasificación')->schema([
                    Forms\Components\Select::make('blog_categoria_id')->label('Categoría')
                        ->relationship('categoria', 'nombre')->searchable()->preload(),
                    Forms\Components\Select::make('etiquetas')->label('Etiquetas')->multiple()
                        ->relationship('etiquetas', 'nombre')->preload()->searchable()
                        ->createOptionForm([Forms\Components\TextInput::make('nombre')->required()]),
                ])->columns(2),


                \Filament\Schemas\Components\Tabs\Tab::make('SEO')->schema([
                    Forms\Components\TextInput::make('meta_title')->maxLength(70)->columnSpanFull(),
                    Forms\Components\Textarea::make('meta_description')->rows(2)->maxLength(320)->columnSpanFull(),
                ]),

                \Filament\Schemas\Components\Tabs\Tab::make('Publicación')->schema([
                    Forms\Components\Select::make('editor_id')->label('Autor')
                        ->relationship('editor', 'nombre')->searchable()->preload()
                        ->helperText('Los autores se crean desde Colaboradores (RRHH), no aquí.'),
                    Forms\Components\DateTimePicker::make('publicado_at')->label('Fecha de publicación'),
                    Forms\Components\Toggle::make('activo')->default(true),
                ])->columns(2),

            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\ImageColumn::make('imagen')->disk('public')->label(''),
            Tables\Columns\TextColumn::make('titulo')->searchable()->sortable()->limit(50),
            Tables\Columns\TextColumn::make('categoria.nombre')->label('Categoría'),
            Tables\Columns\IconColumn::make('activo')->boolean(),
            Tables\Columns\TextColumn::make('publicado_at')->date('d/m/Y')->sortable(),
        ])->defaultSort('publicado_at', 'desc')
          ->filters([Tables\Filters\SelectFilter::make('blog_categoria_id')->relationship('categoria', 'nombre')->label('Categoría')])
          ->actions([Actions\EditAction::make()])
          ->bulkActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListArticulo::route('/'),
            'create' => Pages\CreateArticulo::route('/create'),
            'edit'   => Pages\EditArticulo::route('/{record}/edit'),
        ];
    }
}
