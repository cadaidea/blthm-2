<?php

namespace App\Filament\Pages;
use Filament\Schemas\Schema;

use App\Models\Cuenta;
use App\Services\Contabilidad;
use App\Support\Acl;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class ReportesContables extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-scale';
    protected static string|\UnitEnum|null $navigationGroup = 'Contabilidad';
    protected static ?string $title = 'Balance de comprobación';
    protected static ?string $navigationLabel = 'Balance / Mayor';
    protected static ?int $navigationSort = 3;
    protected string $view = 'filament.pages.reportes-contables';

    public ?array $data = [];
    public array $balance = [];
    public array $totales = ['debe' => 0, 'haber' => 0];

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
                Forms\Components\DatePicker::make('hasta')->label('Hasta')->required()->live()->afterStateUpdated(fn () => $this->calcular()),
            ]),
        ])->statePath('data');
    }

    public function calcular(): void
    {
        $d = $this->form->getState();
        if (empty($d['desde']) || empty($d['hasta'])) return;

        // Balance de comprobación: por cada cuenta de movimiento con actividad.
        $rows = DB::table('asiento_lineas as al')
            ->join('asientos as a', 'a.id', '=', 'al.asiento_id')
            ->join('cuentas as c', 'c.id', '=', 'al.cuenta_id')
            ->where('a.estado', 'registrado')
            ->whereBetween('a.fecha', [$d['desde'], $d['hasta']])
            ->groupBy('c.id', 'c.codigo', 'c.nombre', 'c.tipo')
            ->select('c.codigo', 'c.nombre', 'c.tipo',
                DB::raw('SUM(al.debe) as debe'), DB::raw('SUM(al.haber) as haber'))
            ->orderBy('c.codigo')
            ->get();

        $balance = []; $td = 0; $th = 0;
        foreach ($rows as $r) {
            $debe = (float) $r->debe; $haber = (float) $r->haber;
            $td += $debe; $th += $haber;
            $balance[] = [
                'codigo' => $r->codigo, 'nombre' => $r->nombre, 'tipo' => $r->tipo,
                'debe' => $debe, 'haber' => $haber, 'saldo' => round($debe - $haber, 2),
            ];
        }
        $this->balance = $balance;
        $this->totales = ['debe' => round($td, 2), 'haber' => round($th, 2)];
    }
}
