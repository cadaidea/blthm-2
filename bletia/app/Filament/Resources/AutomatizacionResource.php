<?php

namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;

use App\Filament\Resources\AutomatizacionResource\Pages;
use App\Models\Automatizacion;
use App\Models\Lista;
use App\Support\Acl;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AutomatizacionResource extends Resource
{
    protected static ?string $model = Automatizacion::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bolt';
    protected static ?string $navigationLabel = 'Automatizaciones';
    protected static ?string $modelLabel = 'Automatización';
    protected static ?string $pluralModelLabel = 'Automatizaciones';
    protected static string|\UnitEnum|null $navigationGroup = 'Marketing';

    protected static function permitido(): bool { return Acl::esAdmin() || Acl::esOperaciones(); }
    public static function shouldRegisterNavigation(): bool { return static::permitido(); }
    public static function canViewAny(): bool { return static::permitido(); }

    public static array $tipos = [
        'post_publish'   => 'Al publicar artículo',
        'abandoned_cart' => 'Carrito abandonado',
        'back_in_stock'  => 'Volvió el stock',
        'post_purchase'  => 'Post-compra (secuencia)',
        'winback'        => 'Reactivar inactivos',
        'digest_daily'   => 'Resumen diario',
        'digest_weekly'  => 'Resumen semanal',
        'birthday'       => 'Cumpleaños',
    ];

    public static function canDeleteAny(): bool { return \App\Support\Acl::esAdmin(); }
    public static function canDelete($record): bool { return \App\Support\Acl::esAdmin(); }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('nombre')->required()->maxLength(190),
            Forms\Components\Select::make('tipo')->options(static::$tipos)->required()->native(false),
            Forms\Components\Select::make('estado')->options(['activa' => 'Activa', 'pausada' => 'Pausada'])->default('activa')->native(false),
            Forms\Components\Select::make('lista_ids')->label('Listas destino')->multiple()
                ->options(fn () => Lista::pluck('nombre', 'id')->all()),
            Forms\Components\TextInput::make('asunto')->maxLength(255)
                ->helperText('Variables en asunto: {first_name} {product_name} {post_title}'),
            Forms\Components\TextInput::make('preheader')->maxLength(255),
            \App\Forms\Components\EditorJsField::make('contenido_json')->label('Cuerpo')->columnSpanFull()->helperText('Variables: {first_name} {last_name} {full_name} {email} {site_name} {site_url} {current_year} {cupon} {product_name} {product_url} {product_price} {post_title} {post_url}. El header/footer de marca se añade solo.'),
            Forms\Components\TextInput::make('opciones.dias')->label('Días inactividad (win-back)')->numeric()->default(90),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('nombre')->searchable(),
            Tables\Columns\TextColumn::make('tipo')->badge()->formatStateUsing(fn ($state) => static::$tipos[$state] ?? $state),
            Tables\Columns\TextColumn::make('estado')->badge()->colors(['success' => 'activa', 'gray' => 'pausada']),
            Tables\Columns\TextColumn::make('last_run_at')->label('Última ejecución')->dateTime('d/m/Y H:i')->placeholder('—'),
        ])->actions([Actions\EditAction::make(), Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAutomatizaciones::route('/'),
            'create' => Pages\CreateAutomatizacion::route('/crear'),
            'edit'   => Pages\EditAutomatizacion::route('/{record}/editar'),
        ];
    }
}
