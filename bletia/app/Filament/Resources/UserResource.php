<?php
namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Local;
use App\Models\User;
use App\Support\Acl;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';
    protected static string|\UnitEnum|null $navigationGroup = 'Ajustes';
    protected static ?string $modelLabel = 'Usuario';
    protected static ?string $pluralModelLabel = 'Usuarios';
    protected static ?int $navigationSort = 10;

    public static function getEloquentQuery(): Builder
    {
        return User::query()->with(['local']);
    }

    public static function canViewAny(): bool
    {
        return Acl::esAdmin();
    }


    /** Cada acceso al sistema debe originarse desde un Colaborador (RRHH). No se crean cuentas sueltas aqui. */
    public static function canCreate(): bool
    {
        return false;
    }

    /** El dueno/admin unico nunca se puede eliminar. */
    public static function canDelete($record): bool
    {
        return ! self::esUnicoAdmin($record);
    }

    protected static function esUnicoAdmin($record): bool
    {
        return $record->rol === "admin" && User::where("rol", "admin")->count() <= 1;
    }
    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make()->columns(2)->schema([
                Forms\Components\FileUpload::make('avatar')->label('Foto de perfil')->image()->avatar()
                    ->directory('avatars')->disk('public')->imageEditor()->columnSpanFull(),
                Forms\Components\TextInput::make('name')->label('Nombre')->required(),
                Forms\Components\TextInput::make('email')->label('Correo')->email()->required()->unique(ignoreRecord: true),
                Forms\Components\Select::make('rol')->label('Rol')->options(Acl::ROLES)->default('vendedor')->required()
                    ->helperText('Administrador ve todo. Vendedor ve solo sus pedidos.'),
                Forms\Components\Select::make('local_id')->label('Local')
                    ->options(fn () => Local::where('activo', true)->pluck('nombre', 'id'))
                    ->placeholder('Sin asignar')->searchable(),
                Forms\Components\TextInput::make('password')->label('Contraseña')->password()
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context) => $context === 'create')
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->helperText('Déjalo vacío para no cambiarla.'),
                Forms\Components\Toggle::make('activo')->label('Activo')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('email')->label('Correo')->searchable(),
            Tables\Columns\TextColumn::make('rol')->label('Rol')->badge()
                ->formatStateUsing(fn ($state) => Acl::ROLES[$state] ?? $state)
                ->color(fn ($state) => match ($state) {
                    'admin' => 'danger', 'vendedor' => 'success', 'bodega' => 'warning', default => 'gray',
                }),
            Tables\Columns\TextColumn::make('local.nombre')->label('Local')->placeholder('—'),
            Tables\Columns\IconColumn::make('activo')->label('Activo')->boolean(),
        ])->defaultSort('id', 'desc')
        ->actions([Actions\EditAction::make(), Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
