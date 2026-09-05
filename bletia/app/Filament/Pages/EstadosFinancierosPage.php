<?php

namespace App\Filament\Pages;
use Filament\Schemas\Schema;

use App\Services\EstadosFinancieros;
use App\Support\Acl;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class EstadosFinancierosPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-pie';
    protected static string|\UnitEnum|null $navigationGroup = 'Contabilidad';
    protected static ?string $title = 'Estados financieros';
    protected static ?string $navigationLabel = 'Estados financieros';
    protected static ?int $navigationSort = 6;
    protected string $view = 'filament.pages.estados-financieros';

    public ?array $data = [];
    public array $resultados = [];
    public array $balance = [];

    public static function canAccess(): bool { return \App\Support\Acl::puedeContabilidad(); }

    public static function canViewAny(): bool { return Acl::puedeContabilidad(); }
    public static function shouldRegisterNavigation(): bool { return static::canViewAny(); }

    public function mount(): void
    {
        $this->form->fill([
            'desde' => now()->startOfYear()->toDateString(),
            'hasta' => now()->toDateString(),
        ]);
        $this->calcular();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Grid::make(2)->schema([
                Forms\Components\DatePicker::make('desde')->label('Desde')->required()->live()->afterStateUpdated(fn () => $this->calcular()),
                Forms\Components\DatePicker::make('hasta')->label('Hasta (corte)')->required()->live()->afterStateUpdated(fn () => $this->calcular()),
            ]),
        ])->statePath('data');
    }

    public function calcular(): void
    {
        $d = $this->form->getState();
        if (empty($d['desde']) || empty($d['hasta'])) return;
        $this->resultados = EstadosFinancieros::resultados($d['desde'], $d['hasta']);
        $this->balance = EstadosFinancieros::balance($d['hasta']);
    }
}
