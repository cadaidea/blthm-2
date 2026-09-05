<?php

namespace App\Filament\Pages;
use Filament\Schemas\Schema;


use App\Support\Acl;
use App\Models\Ajuste;
use App\Services\WooImport;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class IntegracionWoo extends Page implements HasForms
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

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-down-on-square-stack';
    protected static string|\UnitEnum|null $navigationGroup = 'Ajustes';
    protected static ?string $title = 'Importar de seridea.ec (Woo)';
    protected static ?string $navigationLabel = 'Importar (WooCommerce)';
    protected static ?int $navigationSort = 11;
    protected string $view = 'filament.pages.integracion-woo';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'woo_url'    => Ajuste::get('woo_url'),
            'woo_key'    => Ajuste::get('woo_key'),
            'woo_secret' => Ajuste::get('woo_secret'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Credenciales WooCommerce (seridea.ec)')->columns(1)->schema([
                Forms\Components\TextInput::make('woo_url')->label('URL de la tienda')->placeholder('https://seridea.ec')->required(),
                Forms\Components\TextInput::make('woo_key')->label('Consumer key')->required(),
                Forms\Components\TextInput::make('woo_secret')->label('Consumer secret')->password()->revealable()->required(),
            ]),
        ])->statePath('data');
    }

    public function guardar(): void
    {
        foreach ($this->form->getState() as $k => $v) Ajuste::set($k, $v);
        Notification::make()->success()->title('Credenciales guardadas')->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('probar')->label('Probar conexión')->icon('heroicon-o-signal')->color('gray')
                ->action(function () {
                    $r = WooImport::probar();
                    $r['ok'] ? Notification::make()->success()->title($r['msg'])->send()
                             : Notification::make()->danger()->title('Sin conexión')->body($r['msg'])->send();
                }),
            Action::make('clientes')->label('Importar clientes')->icon('heroicon-o-users')->color('info')->requiresConfirmation()
                ->action(function () {
                    $r = WooImport::importarClientes();
                    Notification::make()->success()->title('Clientes importados: ' . ($r['total'] ?? 0))->send();
                }),
            Action::make('pedidos')->label('Importar pedidos')->icon('heroicon-o-shopping-bag')->color('success')->requiresConfirmation()
                ->action(function () {
                    $r = WooImport::importarPedidos();
                    Notification::make()->success()->title('Pedidos importados: ' . ($r['total'] ?? 0))->send();
                }),
        ];
    }
}
