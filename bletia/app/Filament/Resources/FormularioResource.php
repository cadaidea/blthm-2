<?php
namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;

use App\Support\Acl;
use App\Filament\Resources\FormularioResource\Pages;
use App\Models\Formulario;
use App\Models\Lista;
use Filament\Forms; use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables; use Filament\Tables\Table;
class FormularioResource extends Resource {
    protected static ?string $model = Formulario::class;

    public static function canViewAny(): bool { return Acl::ve(static::class); }
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder { return \App\Models\Formulario::query(); }
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-plus';
    protected static string|\UnitEnum|null $navigationGroup = 'Marketing';
    protected static ?string $modelLabel = 'Formulario';
    protected static ?string $pluralModelLabel = 'Formularios';
    protected static ?int $navigationSort = 3;

    public static array $camposDisponibles = [
        'nombre'     => 'Nombre',
        'apellido'   => 'Apellido',
        'telefono'   => 'Teléfono',
        'ciudad'     => 'Ciudad',
        'nacimiento' => 'Cumpleaños',
        'acepto'     => 'Casilla "Acepto recibir novedades"',
    ];

    public static function canDeleteAny(): bool { return \App\Support\Acl::esAdmin(); }
    public static function canDelete($record): bool { return \App\Support\Acl::esAdmin(); }

    public static function form(Schema $schema): Schema {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make()->columns(2)->columnSpanFull()->schema([
                Forms\Components\TextInput::make('nombre')->required(),
                Forms\Components\Select::make('tipo')->label('Cómo aparece')->options([
                    'inline' => 'En línea (ubicación fija)', 'popup' => 'Popup', 'slide_in' => 'Deslizante (esquina)',
                    'tab' => 'Pestaña flotante (círculo + panel)',
                    'bar_top' => 'Barra superior', 'bar_bottom' => 'Barra inferior',
                ])->default('inline')->required()->live(),
                Forms\Components\Select::make('estado')->options(['activo' => 'Activo', 'pausado' => 'Pausado'])->default('activo')->required(),
            ]),

            \Filament\Schemas\Components\Section::make('Pestaña')->columns(2)
                ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tipo') === 'tab')
                ->schema([
                    Forms\Components\TextInput::make('opciones.tab_label')->label('Texto del círculo')->default('10% dcto')
                        ->helperText('Corto: cabe en el círculo (64px). Ej: "10% dcto", "Únete".'),
                    Forms\Components\ColorPicker::make('opciones.tab_color')->label('Color del círculo')
                        ->helperText('Vacío = blanco. El texto se ajusta solo a claro/oscuro.'),
                    Forms\Components\FileUpload::make('imagen')->label('Imagen del panel (opcional)')->image()
                        ->disk('public')->directory('digest')->imageEditor()->columnSpanFull(),
                ]),

            \Filament\Schemas\Components\Section::make('Listas destino')->columns(1)->columnSpanFull()->schema([
                Forms\Components\Select::make('lista_ids')->label('Listas')->multiple()
                    ->options(fn () => Lista::pluck('nombre', 'id'))->required()
                    ->helperText('Si activas "el usuario elige", podrá escoger entre estas listas. Si no, se suscribe a todas estas.'),
                Forms\Components\Toggle::make('opciones.elegir_lista')->label('El usuario elige a qué lista suscribirse'),
            ]),

            \Filament\Schemas\Components\Section::make('Ubicación')->columns(2)
                ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tipo') === 'inline')
                ->schema([
                    Forms\Components\Select::make('ubicacion')->label('¿Dónde aparece?')->options([
                        'footer'              => 'Footer (columna)',
                        'blog_sidebar'        => 'Barra lateral del blog',
                        'blog_entre_parrafos' => 'Entre párrafos del artículo',
                        'blog_fin'            => 'Al final del artículo',
                        'checkout'            => 'Checkout (tras términos)',
                    ])->required(),
                    Forms\Components\TextInput::make('entre_parrafo')->label('Después del párrafo N°')->numeric()->default(2)
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('ubicacion') === 'blog_entre_parrafos'),
                    Forms\Components\Toggle::make('premarcado')->label('Casilla premarcada (suscribir por defecto)')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('ubicacion') === 'checkout'),
                ]),

            \Filament\Schemas\Components\Section::make('Dónde mostrarlo')->columns(2)
                ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => in_array($get('tipo'), ['popup', 'slide_in', 'tab', 'bar_top', 'bar_bottom'], true))
                ->schema([
                    Forms\Components\Select::make('ambito')->label('Ámbito')->options([
                        'todo' => 'Todo el sitio', 'blog' => 'Solo blog', 'tienda' => 'Solo tienda', 'paginas' => 'Solo páginas',
                    ])->default('todo'),
                    Forms\Components\Select::make('opciones.trigger')->label('Mostrar cuando')->options([
                        'delay' => 'Pasen X segundos', 'scroll' => 'Baje X %', 'exit' => 'Intente salir',
                    ])->default('delay')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => in_array($get('tipo'), ['popup', 'slide_in'], true)),
                    Forms\Components\TextInput::make('opciones.valor')->label('Valor (seg o %)')->numeric()->default(5)
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => in_array($get('tipo'), ['popup', 'slide_in'], true)),
                    Forms\Components\TextInput::make('opciones.repetir_dias')->label('No repetir (días)')->numeric()->default(7),
                ]),

            \Filament\Schemas\Components\Section::make('Contenido')->columns(2)->columnSpanFull()->schema([
                Forms\Components\TextInput::make('titulo')->label('Título')->columnSpanFull(),
                Forms\Components\Textarea::make('descripcion')->rows(2)->columnSpanFull(),
                Forms\Components\TextInput::make('boton_texto')->label('Texto del botón')->default('Suscribirme'),
                Forms\Components\CheckboxList::make('opciones.campos')->label('Campos a mostrar (además del correo)')
                    ->options(static::$camposDisponibles)->columns(2)->columnSpanFull()
                    ->helperText('El correo siempre se pide.'),
                Forms\Components\CheckboxList::make('opciones.campos_req')->label('De esos, ¿cuáles son obligatorios?')
                    ->options(static::$camposDisponibles)->columns(2)->columnSpanFull()
                    ->helperText('Marca solo los obligatorios. El correo siempre es obligatorio.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table {
        return $table->columns([
            Tables\Columns\TextColumn::make('nombre')->searchable(),
            Tables\Columns\TextColumn::make('tipo')->badge(),
            Tables\Columns\TextColumn::make('ubicacion')->label('Ubicación')->placeholder('—')
                ->formatStateUsing(fn ($state) => match ($state) {
                    'footer' => 'Footer', 'blog_sidebar' => 'Blog · lateral', 'blog_entre_parrafos' => 'Blog · entre párrafos',
                    'blog_fin' => 'Blog · final', 'checkout' => 'Checkout', default => '—',
                }),
            Tables\Columns\TextColumn::make('estado')->badge()->color(fn ($state) => $state === 'activo' ? 'success' : 'gray'),
            Tables\Columns\TextColumn::make('conversiones')->label('Altas'),
        ])->actions([Actions\EditAction::make()])
          ->bulkActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])]);
    }
    public static function getPages(): array {
        return ['index' => Pages\ListFormulario::route('/'), 'create' => Pages\CreateFormulario::route('/create'), 'edit' => Pages\EditFormulario::route('/{record}/edit')];
    }
}
