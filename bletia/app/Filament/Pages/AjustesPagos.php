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

class AjustesPagos extends Page implements HasForms
{
    use InteractsWithForms;

    public static function canAccess(): bool { return Acl::esAdmin(); }
    public static function canViewAny(): bool { return Acl::esAdmin(); }
    public static function shouldRegisterNavigation(): bool { return static::canViewAny(); }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';
    protected static string|\UnitEnum|null $navigationGroup = 'Ajustes';
    protected static ?string $navigationLabel = 'Pagos (Payphone)';
    protected static ?string $title = 'Configuración de pagos (Payphone)';
    protected static ?int $navigationSort = 7;
    protected string $view = 'filament.pages.ajustes-pagos';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'payphone_store_id' => Ajuste::get('payphone_store_id'),
            'payphone_token'    => Ajuste::get('payphone_token'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Payphone — Cajita de Pagos')->columns(1)->schema([
                Forms\Components\TextInput::make('payphone_store_id')->label('Store ID')->required(),
                Forms\Components\TextInput::make('payphone_token')->label('Token')->password()->revealable()->required(),
            ])->description('Credenciales de tu app en Payphone Developer.'),
        ])->statePath('data');
    }

    public function guardar(): void
    {
        foreach ($this->form->getState() as $k => $v) {
            Ajuste::set($k, $v);
        }
        Notification::make()->success()->title('Configuración de Payphone guardada')->send();
    }

    public function probar(): void
    {
        $data = $this->form->getState();
        $storeId = $data['payphone_store_id'] ?? '';
        $token = $data['payphone_token'] ?? '';

        if (! $storeId || ! $token) {
            Notification::make()->danger()->title('Completa Store ID y Token antes de probar')->send();
            return;
        }

        $r = \App\Services\PayPhone::probarCredenciales($storeId, $token);

        if ($r['ok']) {
            Notification::make()->success()->title('Conexión exitosa')->body($r['msg'])->send();
        } else {
            Notification::make()->danger()->title('Falló la prueba')->body($r['msg'])->persistent()->send();
        }
    }
}
