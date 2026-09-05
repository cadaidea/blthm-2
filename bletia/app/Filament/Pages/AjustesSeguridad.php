<?php
namespace App\Filament\Pages;
use Filament\Schemas\Schema;
use App\Models\Ajuste;
use App\Support\Acl;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
class AjustesSeguridad extends Page implements HasForms
{
    use InteractsWithForms;
    public static function canAccess(): bool { return Acl::esAdmin(); }
    public static function canViewAny(): bool { return Acl::esAdmin(); }
    public static function shouldRegisterNavigation(): bool { return static::canViewAny(); }
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';
    protected static string|\UnitEnum|null $navigationGroup = 'Ajustes';
    protected static ?string $navigationLabel = 'Seguridad y anti-spam';
    protected static ?string $title = 'Seguridad y anti-spam';
    protected static ?int $navigationSort = 7;
    protected string $view = 'filament.pages.ajustes-seguridad';
    public ?array $data = [];
    public function mount(): void
    {
        $this->form->fill([
            'turnstile_activo'      => Ajuste::get('turnstile_activo', '0') === '1',
            'turnstile_site_key'    => Ajuste::get('turnstile_site_key'),
            'turnstile_secret_key'  => Ajuste::get('turnstile_secret_key'),
            'contact_email'         => Ajuste::get('contact_email'),
            'contact_topics'        => Ajuste::get('contact_topics'),
        ]);
    }
    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Cloudflare Turnstile (anti-spam en formularios de suscripción)')
                ->description('Protege el formulario de newsletter contra bots. Consigue las claves gratis en dash.cloudflare.com → Turnstile.')
                ->columns(1)->schema([
                    Forms\Components\Toggle::make('turnstile_activo')->label('Activar Turnstile')->live(),
                    Forms\Components\TextInput::make('turnstile_site_key')->label('Site Key (pública)')
                        ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('turnstile_activo'))
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('turnstile_activo')),
                    Forms\Components\TextInput::make('turnstile_secret_key')->label('Secret Key (privada)')->password()->revealable()
                        ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('turnstile_activo'))
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('turnstile_activo')),
                ]),
            \Filament\Schemas\Components\Section::make('Formulario de contacto')
                ->description('A dónde llegan los mensajes del formulario público /contacto. Protegido también por Turnstile arriba.')
                ->columns(1)->schema([
                    Forms\Components\TextInput::make('contact_email')->label('Correo de destino')->email()->placeholder('hola@bletia.ec'),
                    Forms\Components\Textarea::make('contact_topics')->label('Temas del formulario (uno por línea, opcional)')->rows(4)
                        ->helperText('Déjalo vacío para mostrar un campo de asunto libre. Ejemplo: Pieza a medida\nGarantía\nMayoristas\nOtro'),
                ]),
        ])->statePath('data');
    }
    public function guardar(): void
    {
        $state = $this->form->getState();
        Ajuste::set('turnstile_activo', $state['turnstile_activo'] ? '1' : '0');
        Ajuste::set('turnstile_site_key', $state['turnstile_site_key'] ?? '');
        Ajuste::set('turnstile_secret_key', $state['turnstile_secret_key'] ?? '');
        Ajuste::set('contact_email', $state['contact_email'] ?? '');
        Ajuste::set('contact_topics', $state['contact_topics'] ?? '');
        Notification::make()->success()->title('Configuración de seguridad guardada')->send();
    }
}
