<?php

namespace App\Filament\Pages;
use Filament\Schemas\Schema;

use App\Services\LibroTributario;
use App\Support\Acl;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;

class LibroTributarioPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static string|\UnitEnum|null $navigationGroup = 'Contabilidad';
    protected static ?string $title = 'Libro tributario';
    protected static ?string $navigationLabel = 'Libro tributario';
    protected static ?int $navigationSort = 7;
    protected string $view = 'filament.pages.libro-tributario';

    public ?array $data = [];
    public ?array $resumen = null;

    public static function canAccess(): bool { return static::canViewAny(); }

    public static function canViewAny(): bool { return Acl::puedeContabilidad(); }
    public static function shouldRegisterNavigation(): bool { return static::canViewAny(); }

    public function mount(): void
    {
        $this->form->fill([
            'desde' => now()->startOfMonth()->toDateString(),
            'hasta' => now()->endOfMonth()->toDateString(),
        ]);
        $this->calcular();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Grid::make(2)->schema([
                Forms\Components\DatePicker::make('desde')->label('Desde')->required()->live()->afterStateUpdated(fn () => $this->calcular()),
                Forms\Components\DatePicker::make('hasta')->label('Hasta')->required()->live()->afterStateUpdated(fn () => $this->calcular()),
            ]),
        ])->statePath('data');
    }

    public function calcular(): void
    {
        $d = $this->form->getState();
        if (empty($d['desde']) || empty($d['hasta'])) return;
        $r = LibroTributario::periodo($d['desde'], $d['hasta']);
        $this->resumen = $r['resumen'];
    }

    public function descargar()
    {
        $d = $this->form->getState();
        $path = LibroTributario::excel($d['desde'], $d['hasta']);
        return response()->download($path, basename($path))->deleteFileAfterSend(true);
    }

    protected function getFormActions(): array { return []; }
}
