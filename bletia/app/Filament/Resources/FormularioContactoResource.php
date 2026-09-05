<?php
namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;
use App\Filament\Resources\FormularioContactoResource\Pages;
use App\Models\FormularioContacto;
use App\Support\Acl;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FormularioContactoResource extends Resource
{
    protected static ?string $model = FormularioContacto::class;

    public static function canViewAny(): bool { return Acl::esAdmin(); }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-envelope-open';
    protected static string|\UnitEnum|null $navigationGroup = 'Ajustes';
    protected static ?string $modelLabel = 'Formulario de contacto';
    protected static ?string $pluralModelLabel = 'Formularios de contacto';
    protected static ?int $navigationSort = 8;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('nombre')->label('Nombre interno')->required()->placeholder('Contacto general')->columnSpanFull(),
                Forms\Components\TextInput::make('slug')->label('Slug (URL: tudominio.com/slug)')->required()
                    ->rules(['alpha_dash'])->helperText('Solo letras, números y guiones. Ej: contacto, cotizacion, garantia.'),
                Forms\Components\TextInput::make('correo_destino')->label('Correo de destino')->email()->placeholder('hola@bletia.ec')
                    ->helperText('Si lo dejas vacío, se usa el correo remitente configurado en Correo (SMTP).'),
                Forms\Components\Textarea::make('temas')->label('Temas (uno por línea, opcional)')->rows(4)->columnSpanFull()
                    ->helperText('Si lo dejas vacío, el formulario muestra un campo de asunto libre en vez de un desplegable.'),
                Forms\Components\TextInput::make('mensaje_exito')->label('Mensaje de éxito (opcional)')->placeholder('¡Gracias! Te responderemos pronto.')->columnSpanFull(),
                Forms\Components\Toggle::make('activo')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('nombre')->searchable(),
            Tables\Columns\TextColumn::make('slug')->badge()->color('gray'),
            Tables\Columns\TextColumn::make('correo_destino')->label('Correo')->placeholder('— (usa el remitente SMTP)'),
            Tables\Columns\IconColumn::make('activo')->boolean(),
        ])
        ->actions([Actions\EditAction::make()])
        ->bulkActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListFormularioContactos::route('/'),
            'create' => Pages\CreateFormularioContacto::route('/create'),
            'edit'   => Pages\EditFormularioContacto::route('/{record}/edit'),
        ];
    }
}
