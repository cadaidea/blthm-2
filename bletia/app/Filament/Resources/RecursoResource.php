<?php

namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;

use App\Filament\Resources\RecursoResource\Pages;
use App\Models\Cupon;
use App\Models\Lista;
use App\Models\Recurso;
use App\Support\Acl;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class RecursoResource extends Resource
{
    protected static ?string $model = Recurso::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-gift';
    protected static ?string $navigationLabel = 'Recursos (lead magnets)';
    protected static ?string $modelLabel = 'Recurso';
    protected static ?string $pluralModelLabel = 'Recursos';
    protected static string|\UnitEnum|null $navigationGroup = 'Marketing';

    protected static function permitido(): bool { return Acl::esAdmin() || Acl::esOperaciones(); }
    public static function shouldRegisterNavigation(): bool { return static::permitido(); }
    public static function canViewAny(): bool { return static::permitido(); }

    public static function canDeleteAny(): bool { return \App\Support\Acl::esAdmin(); }
    public static function canDelete($record): bool { return \App\Support\Acl::esAdmin(); }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('nombre')->required()->live(onBlur: true)
                ->afterStateUpdated(fn ($state, \Filament\Schemas\Components\Utilities\Set $set) => $set('slug', Str::slug($state))),
            Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true),
            Forms\Components\Textarea::make('descripcion')->rows(2),
            Forms\Components\Select::make('tipo')->options(['archivo' => 'Archivo descargable', 'cupon' => 'Cupón'])
                ->default('archivo')->live()->native(false),
            Forms\Components\FileUpload::make('archivo')->disk('public')->directory('recursos')
                ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tipo') === 'archivo'),
            Forms\Components\Select::make('cupon_id')->label('Cupón del sistema')
                ->options(fn () => Cupon::where('activo', true)->pluck('codigo', 'id'))
                ->helperText('Recomendado: vincula un cupón real (se envía su código).')
                ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tipo') === 'cupon'),
            Forms\Components\TextInput::make('cupon_codigo')->label('… o código manual')
                ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tipo') === 'cupon'),
            Forms\Components\Select::make('lista_ids')->label('Suscribir a listas')->multiple()
                ->options(fn () => Lista::pluck('nombre', 'id')),
            Forms\Components\Toggle::make('activo')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('nombre')->searchable(),
            Tables\Columns\TextColumn::make('tipo')->badge(),
            Tables\Columns\IconColumn::make('activo')->boolean(),
            Tables\Columns\TextColumn::make('descargas')->label('Entregas')->sortable(),
            Tables\Columns\TextColumn::make('slug')->copyable()->formatStateUsing(fn ($state) => url('/recurso/' . $state)),
        ])->actions([Actions\EditAction::make(), Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRecursos::route('/'),
            'create' => Pages\CreateRecurso::route('/crear'),
            'edit'   => Pages\EditRecurso::route('/{record}/editar'),
        ];
    }
}
