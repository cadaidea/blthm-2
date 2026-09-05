<?php

namespace App\Filament\Pages;
use Filament\Schemas\Schema;


use App\Support\Acl;
use App\Services\ExportErp;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Actions\Action as NotifAction;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ExportarErp extends Page implements HasForms
{

    public static function canAccess(): bool { return static::canViewAny(); }

    public static function canViewAny(): bool
    {
        return Acl::esAdmin();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-table-cells';
    protected static string|\UnitEnum|null $navigationGroup = 'Sistema';
    protected static ?string $title = 'Exportar a Excel';
    protected static ?string $navigationLabel = 'Exportar Excel';
    protected static ?int $navigationSort = 3;
    protected string $view = 'filament.pages.exportar-erp';

    public ?array $data = [];

    public function mount(): void { $this->form->fill(); }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Rango de fechas (opcional)')->columns(2)->schema([
                Forms\Components\DatePicker::make('desde')->label('Desde'),
                Forms\Components\DatePicker::make('hasta')->label('Hasta'),
            ]),
        ])->statePath('data');
    }

    public function generar(): void
    {
        $d = $this->form->getState();
        try {
            $archivo = ExportErp::generar($d['desde'] ?? null, $d['hasta'] ?? null);
            $url = ExportErp::url($archivo);
            Notification::make()->success()->title('Excel generado')
                ->body('Pestañas: Pedidos, Despachos, Confirmaciones, Ítems, Proveedores, Transportistas, Movimientos, Suscriptores.')
                ->actions([NotifAction::make('descargar')->label('Descargar')->url($url, shouldOpenInNewTab: true)])
                ->persistent()->send();
        } catch (\Throwable $e) {
            Notification::make()->danger()->title('Error')->body($e->getMessage())->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [Action::make('generar')->label('Generar Excel')->icon('heroicon-o-arrow-down-tray')->action('generar')];
    }
}
