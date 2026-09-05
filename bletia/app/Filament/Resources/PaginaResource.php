<?php
namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;



use App\Forms\Components\EditorJsField;
use App\Support\Acl;
use App\Filament\Resources\PaginaResource\Pages;
use App\Filament\Support\Bloques;
use App\Models\Pagina;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaginaResource extends Resource
{
    protected static ?string $model = Pagina::class;


    public static function canViewAny(): bool
    {
        return Acl::ve(static::class);
    }
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return \App\Models\Pagina::query();
    }
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';
    protected static string|\UnitEnum|null $navigationGroup = 'Blog';
    protected static ?int $navigationSort = 5;
    protected static ?string $modelLabel = 'Página';
    protected static ?string $pluralModelLabel = 'Páginas';

    public static function canDeleteAny(): bool { return \App\Support\Acl::esAdmin(); }
    public static function canDelete($record): bool { return \App\Support\Acl::esAdmin(); }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Tabs::make('Pagina')->columnSpanFull()->tabs([

                \Filament\Schemas\Components\Tabs\Tab::make('Contenido')->schema([
                    Forms\Components\TextInput::make('titulo')->required()->maxLength(191)->columnSpanFull(),
                    Forms\Components\TextInput::make('slug')->maxLength(191)->helperText('URL: /p/slug'),
                    Forms\Components\TextInput::make('orden')->numeric()->default(0),
                    Forms\Components\FileUpload::make('imagen')->image()->directory('paginas')->disk('public')->saveUploadedFileUsing(\App\Support\WebpUpload::handler())->columnSpanFull(),
                    EditorJsField::make('contenido_json')->label('Contenido')->columnSpanFull(),
                    Forms\Components\Builder::make('bloques')->label('Contenido por bloques (recomendado)')->columnSpanFull()
                        ->collapsible()->cloneable()->blockNumbers(false)
                        ->blocks(Bloques::schema()),
                ])->columns(2),

                \Filament\Schemas\Components\Tabs\Tab::make('SEO')->schema([
                    Forms\Components\TextInput::make('meta_title')->maxLength(70)->columnSpanFull(),
                    Forms\Components\Textarea::make('meta_description')->rows(2)->maxLength(320)->columnSpanFull(),
                ]),

                \Filament\Schemas\Components\Tabs\Tab::make('Publicación')->schema([
                    Forms\Components\Toggle::make('activo')->default(true),
                    Forms\Components\Toggle::make('mostrar_en_menu')->label('Mostrar en el menú de páginas'),
                ])->columns(2),

            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('titulo')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('slug'),
            Tables\Columns\IconColumn::make('mostrar_en_menu')->label('En menú')->boolean(),
            Tables\Columns\IconColumn::make('activo')->boolean(),
        ])->reorderable('orden')->defaultSort('orden')
          ->actions([Actions\EditAction::make()])
          ->bulkActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPagina::route('/'),
            'create' => Pages\CreatePagina::route('/create'),
            'edit'   => Pages\EditPagina::route('/{record}/edit'),
        ];
    }
}
