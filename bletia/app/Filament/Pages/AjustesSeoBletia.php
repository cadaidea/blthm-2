<?php

namespace App\Filament\Pages;
use Filament\Schemas\Schema;

use App\Models\Ajuste;
use App\Support\Acl;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class AjustesSeoBletia extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationLabel = 'Identidad & SEO';
    protected static ?string $title = 'Identidad & SEO';
    protected static string|\UnitEnum|null $navigationGroup = 'Ajustes';
    protected string $view = 'filament.pages.ajustes-seo-bletia';

    public ?array $data = [];

    protected static array $claves = [
        'marca', 'eslogan', 'meta_home', 'telefono', 'ciudad', 'provincia', 'pais',
        'sameas', 'og_image', 'ga_id', 'gtm_id',
        'direccion', 'ruc', 'email_logo', 'email_footer_texto', 'email_redes',
        'ai_bots', 'indexnow_key',
    ];

    public static function canAccess(): bool { return Acl::esAdmin(); }

    public function mount(): void
    {
        $vals = [];
        foreach (static::$claves as $k) $vals[$k] = Ajuste::get($k);
        $this->form->fill($vals);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Marca')->columns(2)->schema([
                TextInput::make('marca')->label('Nombre de marca')->placeholder('B L E T I A'),
                TextInput::make('eslogan')->label('Eslogan')->placeholder('Cada pieza define tu espacio'),
            ]),
            Section::make('SEO')->columns(1)->schema([
                Textarea::make('meta_home')->label('Meta descripción (Home)')->rows(2)->maxLength(160)
                    ->helperText('140–160 caracteres. Si está vacío usa el eslogan.'),
                FileUpload::make('og_image')->label('Imagen para compartir (og:image)')->image()
                    ->directory('marca')->disk('public')->imageEditor(),
                \Filament\Forms\Components\Toggle::make('ai_bots')
                    ->label('Permitir rastreadores de IA (GPTBot, ClaudeBot, PerplexityBot, Google-Extended…)')
                    ->helperText('Si lo apagas, esos bots quedan bloqueados en robots.txt. Google y Bing normales no se afectan.')
                    ->default(true),
                TextInput::make('indexnow_key')
                    ->label('Clave IndexNow')
                    ->helperText('Se autogenera al primer uso. Notifica a Bing/Yandex al instante cuando publicas. Déjala como está.')
                    ->suffixAction(\Filament\Actions\Action::make('genIndexNow')->icon('heroicon-o-arrow-path')->action(fn ($set) => $set('indexnow_key', bin2hex(random_bytes(16))))),
            ]),
            Section::make('Negocio (datos estructurados)')->columns(2)->schema([
                TextInput::make('telefono')->label('Teléfono')->tel(),
                TextInput::make('ciudad')->label('Ciudad')->placeholder('Cuenca'),
                TextInput::make('provincia')->label('Provincia')->placeholder('Azuay'),
                TextInput::make('pais')->label('País (código)')->placeholder('EC')->maxLength(2),
                Textarea::make('sameas')->label('Redes / perfiles (sameAs)')->rows(3)->columnSpanFull()
                    ->helperText('Una URL por línea: Instagram, Facebook, ficha de Google, etc.'),
            ]),
            Section::make('Correos (header / footer)')->columns(2)->schema([
                FileUpload::make('email_logo')->label('Logo para correos (PNG, no SVG)')->image()
                    ->directory('marca')->disk('public')
                    ->helperText('Si está vacío se usa el nombre de marca como texto.'),
                TextInput::make('ruc')->label('RUC'),
                TextInput::make('direccion')->label('Dirección')->columnSpanFull()
                    ->placeholder('Av. Principal y Secundaria, Cuenca'),
                Textarea::make('email_footer_texto')->label('Texto de pie (correo)')->rows(2)->columnSpanFull()
                    ->placeholder('Una línea cálida para el pie del correo.'),
                Textarea::make('email_redes')->label('Redes en el correo (una URL por línea)')->rows(3)->columnSpanFull()
                    ->helperText('Solo para correos. Independiente del footer de la web.'),
            ]),
            Section::make('Analítica')->columns(2)->schema([
                TextInput::make('ga_id')->label('Google Analytics (G-…)'),
                TextInput::make('gtm_id')->label('Google Tag Manager (GTM-…)'),
            ]),
        ])->statePath('data');
    }

    public function save(): void
    {
        foreach ($this->form->getState() as $k => $v) {
            if (in_array($k, static::$claves, true)) Ajuste::set($k, is_array($v) ? '' : (string) ($v ?? ''));
        }
        // og_image puede venir como array de FileUpload
        foreach (['og_image', 'email_logo'] as $f) {
            $v = $this->data[$f] ?? null;
            if (is_array($v)) Ajuste::set($f, (string) (reset($v) ?: ''));
        }
        Notification::make()->title('Guardado')->success()->send();
    }

    protected function getFormActions(): array
    {
        return [\Filament\Actions\Action::make('save')->label('Guardar')->submit('save')];
    }
}
