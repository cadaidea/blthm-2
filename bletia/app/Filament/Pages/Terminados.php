<?php

namespace App\Filament\Pages;

use App\Models\PedidoEspecial;
use App\Models\Compra;
use App\Support\Acl;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

/**
 * Vista de solo lectura: lo ya fabricado y cerrado, tanto para CLIENTE
 * (PedidoEspecial entregado) como ABASTECIMIENTO propio (Compra recibida).
 * No modifica nada existente, es complementaria a la Cola de producción.
 */
class Terminados extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-check-circle';
    protected static string|\UnitEnum|null $navigationGroup = 'Producción';
    protected static ?string $navigationLabel = 'Terminados';
    protected static ?string $title = 'Terminados';
    protected static ?int $navigationSort = 1;
    protected string $view = 'filament.pages.terminados';

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

        // ===== Pedidos de cliente entregados =====
        foreach (PedidoEspecial::where('estado_erp', 'entregado')->orderByDesc('updated_at')->limit(100)->get() as $p) {
            $items[] = [
                'origen'   => 'cliente',
                'folio'    => $p->folio ?: ('#' . $p->id),
                'titulo'   => optional($p->cliente)->nombre ?? 'Cliente',
                'detalle'  => \Illuminate\Support\Facades\DB::table('pedido_items')->where('pedido_id', $p->id)->pluck('nombre')->filter()->implode(', '),
                'fecha'    => $p->updated_at ? Carbon::parse($p->updated_at) : null,
                'url'      => \App\Filament\Resources\PedidoEspecialResource::getUrl('view', ['record' => $p->id]),
            ];
        }

        // ===== Órdenes de producción/compra recibidas =====
        foreach (Compra::where('estado', 'recibida')->whereNotNull('recibida_at')->with('items', 'localDestino')->orderByDesc('recibida_at')->limit(100)->get() as $c) {
            $items[] = [
                'origen'   => $c->tipo === 'produccion_interna' ? 'abastecimiento' : 'proveedor',
                'folio'    => $c->folio ?: ('#' . $c->id),
                'titulo'   => ($c->tipo === 'produccion_interna' ? 'Stock propio → ' : 'Compra a proveedor → ') . (optional($c->localDestino)->nombre ?? '—'),
                'detalle'  => $c->items->pluck('nombre')->filter()->implode(', '),
                'fecha'    => $c->recibida_at ? Carbon::parse($c->recibida_at) : null,
                'url'      => \App\Filament\Resources\CompraResource\Pages\ViewCompra::getUrl(['record' => $c->id]),
            ];
        }

        usort($items, fn ($a, $b) => ($b['fecha']?->timestamp ?? 0) <=> ($a['fecha']?->timestamp ?? 0));

        return $items;
    }
}
