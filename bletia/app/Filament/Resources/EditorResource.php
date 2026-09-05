<?php
namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;


use App\Support\Acl;
use App\Filament\Resources\EditorResource\Pages;
use App\Models\Editor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EditorResource extends Resource
{
    protected static ?string $model = Editor::class;


    public static function canViewAny(): bool
    {
        return false; // retirado: fusionado con Colaboradores (RRHH)
    }
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return \App\Models\Editor::query();
    }
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';
    protected static string|\UnitEnum|null $navigationGroup = 'Blog';
    protected static ?string $modelLabel = 'Editor';
    protected static ?string $pluralModelLabel = 'Editores';
    protected static ?int $navigationSort = 4;

    public static function canDeleteAny(): bool { return \App\Support\Acl::esAdmin(); }
    public static function canDelete($record): bool { return \App\Support\Acl::esAdmin(); }
    public static function canCreate(): bool { return \App\Support\Acl::esAdmin(); }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make()->columns(2)->columnSpanFull()->schema([
                Forms\Components\TextInput::make('nombre')->required(),
                Forms\Components\TextInput::make('cargo')->placeholder('Editora de contenidos'),
                Forms\Components\FileUpload::make('foto')->image()->avatar()->directory('editores')->disk('public'),
                Forms\Components\TextInput::make('slug')->helperText('Se genera solo.'),
                Forms\Components\Textarea::make('bio')->rows(3)->columnSpanFull(),
            ]),
            \Filament\Schemas\Components\Section::make('Redes')->columns(2)->collapsed()->columnSpanFull()->schema([
                Forms\Components\TextInput::make('web')->url()->prefix('https://'),
                Forms\Components\TextInput::make('instagram')->url()->prefix('https://'),
                Forms\Components\TextInput::make('facebook')->url()->prefix('https://'),
                Forms\Components\TextInput::make('x')->label('X (Twitter)')->url()->prefix('https://'),
                Forms\Components\TextInput::make('linkedin')->url()->prefix('https://'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\ImageColumn::make('foto')->circular()->disk('public')->label(''),
            Tables\Columns\TextColumn::make('nombre')->searchable(),
            Tables\Columns\TextColumn::make('cargo'),
            Tables\Columns\TextColumn::make('articulos_count')->counts('articulos')->label('Artículos'),
        ])->actions([Actions\EditAction::make()])
          ->bulkActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListEditor::route('/'), 'create' => Pages\CreateEditor::route('/create'), 'edit' => Pages\EditEditor::route('/{record}/edit')];
    }
}
