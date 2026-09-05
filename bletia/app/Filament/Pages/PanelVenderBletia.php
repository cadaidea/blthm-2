<?php

namespace App\Filament\Pages;

use App\Support\Acl;
use Filament\Pages\Page;

class PanelVenderBletia extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel = 'Vender';
    protected static ?string $title = 'Vender';
    protected static string|\UnitEnum|null $navigationGroup = 'Ventas';
    protected static ?int $navigationSort = -6;
    protected string $view = 'filament.pages.panel-vender-bletia';
    protected static ?string $slug = 'vender';

    public static function canAccess(): bool
    {
        return in_array(Acl::rol(), ['admin', 'operaciones', 'contabilidad', 'vendedor'], true);
    }
    public static function shouldRegisterNavigation(): bool { return false; }

    public function accesos(): array
    {
        $url = fn ($cls) => class_exists($cls) ? $cls::getUrl() : '#';
        return [
            'principales' => [
                ['t' => 'Vender stock', 'd' => 'Venta directa de inventario', 'i' => 'heroicon-o-cube', 'c' => '#16a34a', 'u' => CrearVentaBletia::getUrl() . '?modo=stock'],
                ['t' => 'Vender bajo pedido', 'd' => 'Fabricación a medida', 'i' => 'heroicon-o-wrench-screwdriver', 'c' => '#0499FC', 'u' => \App\Filament\Pages\CrearPedidoBletia::getUrl()],
                ['t' => 'Tomar pedido online', 'd' => 'Adjudicar pedidos web', 'i' => 'heroicon-o-inbox-arrow-down', 'c' => '#7c3aed', 'u' => $url(\App\Filament\Resources\WooPedidoResource::class)],
                ['t' => 'Tomar venta stock online', 'd' => 'Adjudicar ventas web', 'i' => 'heroicon-o-shopping-bag', 'c' => '#db2777', 'u' => $url(\App\Filament\Resources\WooPedidoResource::class)],
            ],
            'listas' => [
                ['t' => 'Pedidos', 'i' => 'heroicon-o-clipboard-document-list', 'u' => $url(\App\Filament\Resources\PedidoEspecialResource::class)],
                ['t' => 'Ventas', 'i' => 'heroicon-o-banknotes', 'u' => $url(\App\Filament\Resources\VentaResource::class)],
                ['t' => 'Fabricación', 'i' => 'heroicon-o-cog-6-tooth', 'u' => $url(\App\Filament\Resources\ProduccionResource::class)],
                ['t' => 'Despacho', 'i' => 'heroicon-o-truck', 'u' => $url(\App\Filament\Resources\DespachoResource::class)],
            ],
        ];
    }
}
