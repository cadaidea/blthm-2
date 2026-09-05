<?php

namespace App\Filament\Pages;

use App\Models\Pedido;
use App\Models\WooPedido;
use App\Services\TomarOnline;
use App\Support\Acl;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class TomarOnlineBletia extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox-arrow-down';
    protected static ?string $navigationLabel = 'Tomar online';
    protected static ?string $title = 'Pedidos online por tomar';
    protected static string|\UnitEnum|null $navigationGroup = 'Ventas';
    protected static ?int $navigationSort = 3;
    protected string $view = 'filament.pages.tomar-online-bletia';

    public static function canAccess(): bool { return in_array(Acl::rol(), ['admin', 'operaciones', 'vendedor'], true); }
    public static function shouldRegisterNavigation(): bool { return static::canAccess(); }

    public function propios(): array
    {
        return TomarOnline::pendientesPropios()->map(function (Pedido $p) {
            $items = \Illuminate\Support\Facades\DB::table('pedido_items')->where('pedido_id', $p->id)->get();
            $todoStock = true;
            foreach ($items as $it) {
                if (! $it->producto_id || TomarOnline::stockDe((int) $it->producto_id) < (int) $it->cantidad) { $todoStock = false; break; }
            }
            return [
                'id' => $p->id, 'origen' => 'Bletia.ec', 'color' => '#0499FC',
                'cliente' => optional($p->cliente)->nombre ?? ($p->email ?: '—'),
                'total' => (float) $p->total, 'fecha' => optional($p->created_at)->format('d/m H:i') ?? '—',
                'items' => $items->count(), 'destino' => $todoStock ? 'Stock → despacho' : 'A fabricar',
                'metodo' => 'PayPhone · tarjeta',
            ];
        })->all();
    }

    public function woo(): array
    {
        return TomarOnline::pendientesWoo()->map(function (WooPedido $w) {
            return [
                'id' => $w->id, 'woo_id' => $w->woo_id, 'origen' => 'WooCommerce', 'color' => '#7c3aed',
                'cliente' => $w->cliente_nombre ?: ($w->cliente_email ?: '—'),
                'total' => (float) $w->total, 'fecha' => optional($w->fecha)->format('d/m H:i') ?? '—',
                'items' => $w->items()->count(), 'numero' => $w->numero,
                'metodo' => 'WooCommerce',
            ];
        })->all();
    }

    public function tomarPropio(int $id): void
    {
        $p = Pedido::find($id);
        if (! $p) { Notification::make()->danger()->title('Pedido no encontrado')->send(); return; }
        $r = TomarOnline::tomarPropio($p, auth()->id());
        $r['ok'] ? Notification::make()->success()->title('Pedido tomado')->body($r['msg'])->send()
                 : Notification::make()->danger()->title('No se pudo tomar')->body($r['msg'])->send();
    }

    public function tomarWoo(int $id): void
    {
        $w = WooPedido::find($id);
        if (! $w) { Notification::make()->danger()->title('Pedido Woo no encontrado')->send(); return; }
        $r = TomarOnline::tomarWoo($w, auth()->id());
        $r['ok'] ? Notification::make()->success()->title('Woo tomado')->body($r['msg'])->send()
                 : Notification::make()->danger()->title('No se pudo tomar')->body($r['msg'])->send();
    }
}
