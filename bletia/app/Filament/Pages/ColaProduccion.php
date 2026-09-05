<?php

namespace App\Filament\Pages;

use App\Models\PedidoEspecial;
use App\Models\Compra;
use App\Support\Acl;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

/**
 * Cola única de producción: une en una sola vista lo que el taller debe fabricar,
 * sea para un CLIENTE (PedidoEspecial, estado en_produccion) o para ABASTECIMIENTO
 * propio (Compra, tipo=produccion_interna, no terminada). Ordenado por urgencia.
 * No modifica nada de ProduccionResource ni del flujo de pedidos existente: es
 * una vista adicional de solo lectura que junta ambas fuentes para planificación.
 */
class ColaProduccion extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-queue-list';
    protected static string|\UnitEnum|null $navigationGroup = 'Producción';
    protected static ?string $navigationLabel = 'Cola de producción';
    protected static ?string $title = 'Cola de producción';
    protected static ?int $navigationSort = 0;
    protected string $view = 'filament.pages.cola-produccion';

    public static function canAccess(): bool { return static::canViewAny(); }

    public static function canViewAny(): bool
    {
        return Acl::ve(static::class) || in_array(Acl::rol(), ['admin', 'operaciones', 'produccion'], true);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public function getItems(): array
    {
        $items = [];
        $hoy = Carbon::today();

        // ===== Pedidos de cliente en fabricación =====
        foreach (PedidoEspecial::where('estado_erp', 'en_produccion')->get() as $p) {
            $fechaLimite = $p->fecha_comprometida ? Carbon::parse($p->fecha_comprometida) : null;
            $diasFab = (int) (\App\Models\PedidoItemErp::where('pedido_id', $p->id)
                ->join('productos', 'productos.id', '=', 'pedido_items.producto_id')
                ->max('productos.dias_fabricacion') ?: 0);
            $items[] = [
                'origen'      => 'cliente',
                'folio'       => $p->folio ?: ('#' . $p->id),
                'titulo'      => optional($p->cliente)->nombre ?? 'Cliente',
                'detalle'     => \Illuminate\Support\Facades\DB::table('pedido_items')->where('pedido_id', $p->id)->pluck('nombre')->filter()->implode(', '),
                'fecha_limite'=> $fechaLimite,
                'dias_fab'    => $diasFab,
                'atrasado'    => $fechaLimite ? $fechaLimite->lt($hoy) : false,
                'url'         => \App\Filament\Resources\PedidoEspecialResource::getUrl('view', ['record' => $p->id]),
            ];
        }

        // ===== Órdenes de producción interna (abastecimiento) =====
        foreach (Compra::where('tipo', 'produccion_interna')->whereNotIn('estado', ['recibida', 'anulada'])->with('items.producto', 'localDestino')->get() as $c) {
            $diasFab = (int) ($c->items->max(fn ($it) => optional($it->producto)->dias_fabricacion) ?: 0);
            $items[] = [
                'origen'      => 'abastecimiento',
                'folio'       => $c->folio ?: ('#' . $c->id),
                'titulo'      => 'Stock propio → ' . (optional($c->localDestino)->nombre ?? '—'),
                'detalle'     => $c->items->pluck('nombre')->filter()->implode(', '),
                'fecha_limite'=> null, // el abastecimiento no tiene fecha comprometida a cliente
                'dias_fab'    => $diasFab,
                'atrasado'    => false,
                'url'         => \App\Filament\Resources\CompraResource\Pages\ViewCompra::getUrl(['record' => $c->id]),
            ];
        }

        // ===== orden: atrasados primero, luego por fecha límite más próxima, sin fecha al final =====
        usort($items, function ($a, $b) {
            if ($a['atrasado'] !== $b['atrasado']) return $a['atrasado'] ? -1 : 1;
            if ($a['fecha_limite'] && $b['fecha_limite']) return $a['fecha_limite']->lt($b['fecha_limite']) ? -1 : 1;
            if ($a['fecha_limite']) return -1;
            if ($b['fecha_limite']) return 1;
            return 0;
        });

        return $items;
    }
}
