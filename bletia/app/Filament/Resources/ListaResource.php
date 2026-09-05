<?php
namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;

use App\Support\Acl;
use App\Filament\Resources\ListaResource\Pages;
use App\Models\Lista;
use Filament\Forms; use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables; use Filament\Tables\Table;
class ListaResource extends Resource {
    protected static ?string $model = Lista::class;


    public static function canViewAny(): bool
    {
        return Acl::ve(static::class);
    }
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return \App\Models\Lista::query();
    }
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static string|\UnitEnum|null $navigationGroup = 'Marketing';
    protected static ?string $modelLabel = 'Lista';
    protected static ?string $pluralModelLabel = 'Listas';
    protected static ?int $navigationSort = 2;
    public static function canDeleteAny(): bool { return \App\Support\Acl::esAdmin(); }
    public static function canDelete($record): bool { return \App\Support\Acl::esAdmin(); }

    public static function form(Schema $schema): Schema {
        return $schema->schema([
            Forms\Components\TextInput::make('nombre')->required(),
            Forms\Components\Textarea::make('descripcion')->rows(2),
            Forms\Components\Toggle::make('publica')->label('Visible en preferencias')->default(true),
        ]);
    }
    public static function table(Table $table): Table {
        return $table->columns([
            Tables\Columns\TextColumn::make('nombre')->searchable(),
            Tables\Columns\TextColumn::make('suscriptores_count')->counts('suscriptores')->label('Suscriptores'),
            Tables\Columns\IconColumn::make('publica')->boolean(),
        ])->actions([Actions\EditAction::make()])
          ->bulkActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])]);
    }
    public static function getPages(): array {
        return ['index' => Pages\ListLista::route('/'), 'create' => Pages\CreateLista::route('/create'), 'edit' => Pages\EditLista::route('/{record}/edit')];
    }
}
