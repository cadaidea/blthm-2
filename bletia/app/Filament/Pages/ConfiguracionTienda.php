<?php

namespace App\Filament\Pages;
use Filament\Schemas\Schema;


use App\Support\Acl;
use App\Filament\Support\Bloques;
use App\Models\Ajuste;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ConfiguracionTienda extends Page implements HasForms
{

    public static function canAccess(): bool { return \App\Support\Acl::esAdmin(); }

    public static function canViewAny(): bool
    {
        return Acl::esAdmin();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-paint-brush';
    protected static string|\UnitEnum|null $navigationGroup = 'Ajustes';
    protected static ?string $navigationLabel = 'Marca y apariencia';
    protected static ?string $title = 'Marca y apariencia';
    protected string $view = 'filament.pages.configuracion-tienda';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'color_primario'        => Ajuste::get('color_primario'),
            'color_secundario'      => Ajuste::get('color_secundario'),
            'color_footer'          => Ajuste::get('color_footer'),
            'logo'                  => Ajuste::get('logo') ?: null,
            'logo_claro'            => Ajuste::get('logo_claro') ?: null,
            'logo_movil'            => Ajuste::get('logo_movil') ?: null,
            'favicon'               => Ajuste::get('favicon') ?: null,
            'pedido_auto_sin_stock' => Ajuste::get('pedido_auto_sin_stock', '0') === '1',
            'url_tienda' => Ajuste::get('url_tienda', '/'),
            'home_hero_img'    => Ajuste::get('home_hero_img') ?: null,
            'home_hero_titulo' => Ajuste::get('home_hero_titulo'),
            'home_hero_texto'  => Ajuste::get('home_hero_texto'),
            'home_hero_cta'    => Ajuste::get('home_hero_cta'),
            'home_hero_cta_url'=> Ajuste::get('home_hero_cta_url'),
            'home_intro_titulo'=> Ajuste::get('home_intro_titulo'),
            'home_intro_texto' => Ajuste::get('home_intro_texto'),
            'home_producto_id' => Ajuste::get('home_producto_id') ?: null,
            'home_bloques'     => json_decode(Ajuste::get('home_bloques') ?: '[]', true) ?: [],
            'footer_texto'     => Ajuste::get('footer_texto'),
            'footer_img'       => Ajuste::get('footer_img') ?: null,
            'footer_bg'        => Ajuste::get('footer_bg'),
            'footer_text'      => Ajuste::get('footer_text'),
            'footer_nosotros'  => json_decode(Ajuste::get('footer_nosotros') ?: '[]', true) ?: [],
            'footer_legal'     => json_decode(Ajuste::get('footer_legal') ?: '[]', true) ?: [],
            'footer_recursos'  => json_decode(Ajuste::get('footer_recursos') ?: '[]', true) ?: [],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Colores')->columns(3)->schema([
                Forms\Components\ColorPicker::make('color_primario')->label('Primario (marca)'),
                Forms\Components\ColorPicker::make('color_secundario')->label('Secundario (botones)'),
                Forms\Components\ColorPicker::make('color_footer')->label('Fondo del footer'),
            ]),
            \Filament\Schemas\Components\Section::make('Logos e ícono')->columns(2)->schema([
                Forms\Components\FileUpload::make('logo')->label('Logo (cabecera fija) .svg')
                    ->acceptedFileTypes(['image/svg+xml'])->directory('marca')->disk('public'),
                Forms\Components\FileUpload::make('logo_claro')->label('Logo claro (cabecera transparente) .svg')
                    ->acceptedFileTypes(['image/svg+xml'])->directory('marca')->disk('public'),
                Forms\Components\FileUpload::make('logo_movil')->label('Logo móvil .svg/.png')
                    ->acceptedFileTypes(['image/svg+xml', 'image/png'])->directory('marca')->disk('public'),
                Forms\Components\FileUpload::make('favicon')->label('Favicon .png')->image()
                    ->acceptedFileTypes(['image/png'])->directory('marca')->disk('public'),
            ]),
            \Filament\Schemas\Components\Section::make('Ventas')->schema([
                Forms\Components\Toggle::make('pedido_auto_sin_stock')
                    ->label('Permitir compra "bajo pedido" automáticamente cuando un producto llega a 0 de stock'),
                Forms\Components\TextInput::make('url_tienda')->label('URL de tienda (botón "Ver productos" del carrito vacío)')
                    ->placeholder('/ o /shop o https://...')->default('/'),
            ]),
            \Filament\Schemas\Components\Section::make('Home (portada)')->columns(2)->schema([
                Forms\Components\FileUpload::make('home_hero_img')->label('Imagen principal (hero)')->image()->directory('home')->disk('public')->saveUploadedFileUsing(\App\Support\WebpUpload::handler())->columnSpanFull(),
                Forms\Components\TextInput::make('home_hero_titulo')->label('Título hero'),
                Forms\Components\TextInput::make('home_hero_cta')->label('Texto del botón'),
                Forms\Components\Textarea::make('home_hero_texto')->label('Texto hero')->rows(2)->columnSpanFull(),
                Forms\Components\TextInput::make('home_hero_cta_url')->label('URL del botón')->columnSpanFull(),
                Forms\Components\TextInput::make('home_intro_titulo')->label('Título intro'),
                Forms\Components\Select::make('home_producto_id')->label('Producto destacado')->options(\App\Models\Producto::where('activo',true)->pluck('nombre','id'))->searchable(),
                Forms\Components\Textarea::make('home_intro_texto')->label('Texto intro')->rows(2)->columnSpanFull(),
            ]),
            \Filament\Schemas\Components\Section::make('Home — Contenido por bloques')->description('Se muestra bajo la intro de la portada. Mismos bloques que las Páginas, con opción "Ancho completo".')->schema([
                Forms\Components\Builder::make('home_bloques')->label('')->columnSpanFull()
                    ->collapsible()->cloneable()->blockNumbers(false)
                    ->blocks(Bloques::schema()),
            ]),
            \Filament\Schemas\Components\Section::make('Footer')->schema([
                \Filament\Schemas\Components\Grid::make(3)->schema([
                    Forms\Components\FileUpload::make('footer_img')->label('Imagen de marca (footer) .svg/.png')
                        ->acceptedFileTypes(['image/svg+xml', 'image/png'])->directory('marca')->disk('public'),
                    Forms\Components\ColorPicker::make('footer_bg')->label('Color de fondo (si vacío, usa "Fondo del footer")'),
                    Forms\Components\ColorPicker::make('footer_text')->label('Color de texto'),
                ]),
                Forms\Components\Textarea::make('footer_texto')->label('Texto bajo el logo')->rows(2),
                Forms\Components\Repeater::make('footer_nosotros')->label('Menú "Nosotros"')->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('titulo')->required(),
                        Forms\Components\TextInput::make('url')->required()->placeholder('/nosotros o https://...'),
                    ])->defaultItems(0)->addActionLabel('Agregar enlace')->reorderable()->collapsed(),
                Forms\Components\Repeater::make('footer_legal')->label('Menú "Legal" (aparece centrado abajo)')->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('titulo')->required(),
                        Forms\Components\TextInput::make('url')->required()->placeholder('/terminos o https://...'),
                    ])->defaultItems(0)->addActionLabel('Agregar enlace')->reorderable()->collapsed(),
                Forms\Components\Repeater::make('footer_recursos')->label('Menú "Recursos"')->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('titulo')->required(),
                        Forms\Components\TextInput::make('url')->required()->placeholder('/guia o https://...'),
                    ])->defaultItems(0)->addActionLabel('Agregar enlace')->reorderable()->collapsed(),
            ]),
        ])->statePath('data');
    }

    public function save(): void
    {
        $d = $this->form->getState();
        foreach ($d as $k => $v) {
            if ($k === 'pedido_auto_sin_stock') {
                $v = $v ? '1' : '0';
            }
            Ajuste::set($k, is_array($v) ? json_encode($v) : (string) ($v ?? ''));
        }
        Notification::make()->success()->title('Configuración guardada')->send();
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')->label('Guardar')->submit('save'),
        ];
    }
}
