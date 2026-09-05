<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ErpStats extends BaseWidget
{
    public static function canView(): bool { return false; }

    protected ?string $pollingInterval = null;
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $hoy = now()->startOfDay();

        $especialesActivos = Schema::hasColumn('pedidos', 'estado_erp')
            ? DB::table('pedidos')->where('tipo_erp', 'pedido_especial')
                ->whereNotIn('estado_erp', ['entregado', 'cancelado'])->count()
            : 0;

        $despPend = Schema::hasTable('despachos')
            ? DB::table('despachos')->whereIn('estado', ['programado', 'en_transito'])->count() : 0;

        $entregadosHoy = Schema::hasTable('historial_pedido')
            ? DB::table('historial_pedido')->where('estado_nuevo', 'entregado')->where('creado_en', '>=', $hoy)->count() : 0;

        $confHoy = Schema::hasTable('confirmaciones')
            ? DB::table('confirmaciones')->where('confirmado_en', '>=', $hoy)->count() : 0;

        $totalEspeciales = DB::table('pedidos')->where('tipo_erp', 'pedido_especial')->count();

        return [
            Stat::make('Pedidos especiales activos', (string) $especialesActivos)->color('info')->icon('heroicon-o-wrench-screwdriver'),
            Stat::make('Despachos pendientes', (string) $despPend)->color($despPend ? 'warning' : 'gray')->icon('heroicon-o-truck'),
            Stat::make('Entregados hoy', (string) $entregadosHoy)->color('success')->icon('heroicon-o-check-circle'),
            Stat::make('Confirmaciones hoy', (string) $confHoy)->color('success')->icon('heroicon-o-camera'),
            Stat::make('Total pedidos especiales', (string) $totalEspeciales)->color('gray'),
        ];
    }
}
