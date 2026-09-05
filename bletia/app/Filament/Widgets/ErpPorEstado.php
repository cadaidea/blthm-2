<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ErpPorEstado extends Widget
{
    public static function canView(): bool { return false; }

    protected string $view = 'filament.widgets.erp-por-estado';
    protected int|string|array $columnSpan = 'full';

    public function getEstados(): array
    {
        if (! Schema::hasColumn('pedidos', 'estado_erp')) return [];
        $labels = \App\Services\EstadoPedidoErp::ESTADOS;
        $rows = DB::table('pedidos')->where('tipo_erp', 'pedido_especial')
            ->select('estado_erp', DB::raw('count(*) as n'))->groupBy('estado_erp')->pluck('n', 'estado_erp')->all();
        $out = [];
        foreach ($labels as $k => $lbl) {
            if (in_array($k, ['cancelado'], true)) continue;
            $out[] = ['estado' => $lbl, 'n' => (int) ($rows[$k] ?? 0)];
        }
        return $out;
    }
}
