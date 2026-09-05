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

class AjustesSmtp extends Page implements HasForms
{
    use InteractsWithForms;

    public static function canAccess(): bool { return Acl::esAdmin(); }
    public static function canViewAny(): bool { return Acl::esAdmin(); }
    public static function shouldRegisterNavigation(): bool { return static::canViewAny(); }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-envelope';
    protected static string|\UnitEnum|null $navigationGroup = 'Ajustes';
    protected static ?string $navigationLabel = 'Correo (SMTP)';
    protected static ?string $title = 'Configuración de correo (SMTP)';
    protected static ?int $navigationSort = 6;
    protected string $view = 'filament.pages.ajustes-smtp';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'smtp_host'         => Ajuste::get('smtp_host'),
            'smtp_port'         => Ajuste::get('smtp_port'),
            'smtp_encryption'   => Ajuste::get('smtp_encryption', 'tls'),
            'smtp_username'     => Ajuste::get('smtp_username'),
            'smtp_password'     => Ajuste::get('smtp_password'),
            'smtp_from_address' => Ajuste::get('smtp_from_address'),
            'smtp_from_name'    => Ajuste::get('smtp_from_name'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Servidor SMTP')->columns(2)->schema([
                Forms\Components\TextInput::make('smtp_host')->label('Host')->placeholder('smtp.sender.net')->required(),
                Forms\Components\TextInput::make('smtp_port')->label('Puerto')->numeric()->placeholder('587')->required(),
                Forms\Components\Select::make('smtp_encryption')->label('Encriptación')
                    ->options(['tls' => 'TLS', 'ssl' => 'SSL', '' => 'Ninguna'])->default('tls')->native(false),
                Forms\Components\TextInput::make('smtp_username')->label('Usuario')->required(),
                Forms\Components\TextInput::make('smtp_password')->label('Contraseña')->password()->revealable()->required(),
            ]),
            \Filament\Schemas\Components\Section::make('Remitente')->columns(2)->schema([
                Forms\Components\TextInput::make('smtp_from_address')->label('Correo remitente')->email()->required(),
                Forms\Components\TextInput::make('smtp_from_name')->label('Nombre remitente')->required(),
            ]),
        ])->statePath('data');
    }

    public function guardar(): void
    {
        foreach ($this->form->getState() as $k => $v) {
            Ajuste::set($k, $v);
        }
        Notification::make()->success()->title('Configuración de correo guardada')->send();
    }
}
