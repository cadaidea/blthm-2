<?php

namespace App\Filament\Pages;

use App\Support\Acl;
use App\Models\Local;
use App\Models\Producto;
use Filament\Pages\Page;

class Inventario extends Page
{
    public static function canAccess(): bool { return static::canViewAny(); }

    public static function canViewAny(): bool
    {
        return Acl::ve(static::class);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static string|\UnitEnum|null $navigationGroup = 'Inventario';
    protected static ?string $navigationLabel = 'Stock por bodega';
    protected static ?string $title = 'Stock por bodega';
    protected static ?int $navigationSort = 1;
    protected string $view = 'filament.pages.inventario';

    public function getLocales()
    {
        return Local::orderBy('nombre')->get();
    }

    public function getFilas()
    {
        $locales = $this->getLocales();
        return Producto::with(['stock', 'variantes.stock'])->orderBy('nombre')->get()->map(function (Producto $p) use ($locales) {
            $por = [];
            $total = 0;
            // stock general (sin variante)
            $stockGeneral = $p->stock->whereNull('variante_id');
            foreach ($locales as $l) {
                $cant = (int) $stockGeneral->where('local_id', $l->id)->sum('cantidad');
                $min  = (int) optional($stockGeneral->firstWhere('local_id', $l->id))->minimo;
                $por[$l->id] = ['cant' => $cant, 'bajo' => $min > 0 && $cant <= $min];
                $total += $cant;
            }

            // desglose por combinación/variante
            $variantes = $p->variantes->map(function ($v) use ($locales) {
                $porVar = [];
                $totalVar = 0;
                foreach ($locales as $l) {
                    $cant = (int) $v->stock->where('local_id', $l->id)->sum('cantidad');
                    $min  = (int) optional($v->stock->firstWhere('local_id', $l->id))->minimo;
                    $porVar[$l->id] = ['cant' => $cant, 'bajo' => $min > 0 && $cant <= $min];
                    $totalVar += $cant;
                }
                return ['label' => $v->combo_label, 'por' => $porVar, 'total' => $totalVar];
            })->filter(fn ($v) => $v['total'] > 0 || true); // mostrar todas, con o sin stock

            // si el producto tiene variantes, el total real es la suma de las variantes (no del stock general)
            if ($variantes->isNotEmpty()) {
                $total = $variantes->sum('total');
                foreach ($locales as $l) {
                    $por[$l->id]['cant'] = $variantes->sum(fn ($v) => $v['por'][$l->id]['cant']);
                }
            }

            return ['nombre' => $p->nombre, 'por' => $por, 'total' => $total, 'variantes' => $variantes];
        });
    }
}
