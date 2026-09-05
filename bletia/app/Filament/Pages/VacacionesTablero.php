<?php

namespace App\Filament\Pages;
use Filament\Schemas\Schema;

use App\Services\VacacionesControl;
use App\Support\Acl;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class VacacionesTablero extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';
    protected static string|\UnitEnum|null $navigationGroup = 'Nómina / RRHH';
    protected static ?string $title = 'Vacaciones · saldos';
    protected static ?string $navigationLabel = 'Saldos de vacaciones';
    protected static ?int $navigationSort = 7;
    protected string $view = 'filament.pages.vacaciones-tablero';

    public ?array $data = [];
    public array $filas = [];

    public static function canAccess(): bool { return \App\Support\Acl::esAdmin(); }

    public static function canViewAny(): bool { return Acl::esAdmin(); }
    public static function shouldRegisterNavigation(): bool { return static::canViewAny(); }

    public function mount(): void
    {
        $this->form->fill(['hasta' => now()->toDateString()]);
        $this->cargar();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\DatePicker::make('hasta')->label('Saldo a la fecha')->required()->live()->afterStateUpdated(fn () => $this->cargar()),
        ])->statePath('data');
    }

    public function cargar(): void
    {
        $hasta = $this->form->getState()['hasta'] ?? now()->toDateString();
        $this->filas = VacacionesControl::resumen($hasta);
    }
}
