<?php
namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;

use App\Support\Acl;
use App\Filament\Resources\SuscriptorResource\Pages;
use App\Models\Suscriptor;
use Filament\Forms; use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables; use Filament\Tables\Table;
class SuscriptorResource extends Resource {
    protected static ?string $model = Suscriptor::class;


    public static function canViewAny(): bool
    {
        return Acl::ve(static::class);
    }
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return \App\Models\Suscriptor::query()->with(['listas']);
    }
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';
    protected static string|\UnitEnum|null $navigationGroup = 'Marketing';
    protected static ?string $modelLabel = 'Suscriptor';
    protected static ?string $pluralModelLabel = 'Suscriptores';
    protected static ?int $navigationSort = 1;
    public static function canDeleteAny(): bool { return \App\Support\Acl::esAdmin(); }
    public static function canDelete($record): bool { return \App\Support\Acl::esAdmin(); }

    public static function form(Schema $schema): Schema {
        return $schema->schema([
            Forms\Components\TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('nombre'),
            Forms\Components\TextInput::make('apellido'),
            Forms\Components\Select::make('estado')->options([
                'pendiente' => 'Pendiente', 'confirmado' => 'Confirmado', 'baja' => 'Baja', 'rebotado' => 'Rebotado',
            ])->default('confirmado')->required(),
            Forms\Components\Select::make('listas')->multiple()->relationship('listas', 'nombre')->preload(),
        ]);
    }
    public static function table(Table $table): Table {
        return $table->columns([
            Tables\Columns\TextColumn::make('email')->searchable()->copyable(),
            Tables\Columns\TextColumn::make('nombre_completo')->label('Nombre'),
            Tables\Columns\TextColumn::make('estado')->badge()->color(fn ($state) => match ($state) {
                'confirmado' => 'success', 'pendiente' => 'warning', 'rebotado' => 'danger', default => 'gray',
            }),
            Tables\Columns\TextColumn::make('listas.nombre')->badge()->label('Listas'),
            Tables\Columns\TextColumn::make('created_at')->date('d/m/Y')->label('Alta')->sortable(),
        ])->defaultSort('created_at', 'desc')
          ->filters([
            Tables\Filters\SelectFilter::make('estado')->options([
                'pendiente' => 'Pendiente', 'confirmado' => 'Confirmado', 'baja' => 'Baja', 'rebotado' => 'Rebotado',
            ]),
            Tables\Filters\SelectFilter::make('listas')->relationship('listas', 'nombre'),
          ])
          ->actions([Actions\EditAction::make()])
          ->bulkActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])]);
    }
    public static function getPages(): array {
        return ['index' => Pages\ListSuscriptor::route('/'), 'create' => Pages\CreateSuscriptor::route('/create'), 'edit' => Pages\EditSuscriptor::route('/{record}/edit')];
    }
}
