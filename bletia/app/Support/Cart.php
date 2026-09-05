<?php

namespace App\Support;

use App\Models\Producto;
use App\Models\Variante;
use Illuminate\Support\Facades\Session;

class Cart
{
    const KEY = 'carrito';

    protected static function raw(): array { return (array) Session::get(self::KEY, []); }
    protected static function guardar(array $d): void { Session::put(self::KEY, $d); }

    protected static function key(int $productoId, ?int $varianteId): string
    {
        return $productoId . '|' . ((int) $varianteId);
    }

    public static function agregar(int $productoId, ?int $varianteId = null, int $cantidad = 1): void
    {
        $d = self::raw();
        $k = self::key($productoId, $varianteId);
        $d[$k] = [
            'producto_id' => $productoId,
            'variante_id' => $varianteId ? (int) $varianteId : null,
            'cantidad'    => max(1, ($d[$k]['cantidad'] ?? 0) + $cantidad),
        ];
        self::guardar($d);
    }

    public static function actualizar(string $key, int $cantidad): void
    {
        $d = self::raw();
        if (! isset($d[$key])) return;
        if ($cantidad <= 0) unset($d[$key]); else $d[$key]['cantidad'] = $cantidad;
        self::guardar($d);
    }

    public static function eliminar(string $key): void { $d = self::raw(); unset($d[$key]); self::guardar($d); }
    public static function vaciar(): void { Session::forget(self::KEY); }

    public static function lineas(): array
    {
        $d = self::raw();
        if (! $d) return [];
        $pids = array_values(array_unique(array_map(fn ($l) => (int) $l['producto_id'], $d)));
        $productos = Producto::with('imagenes')->whereIn('id', $pids)->where('activo', true)->get()->keyBy('id');
        $vids = array_values(array_filter(array_map(fn ($l) => $l['variante_id'] ?? null, $d)));
        $variantes = $vids ? Variante::with('producto')->whereIn('id', $vids)->get()->keyBy('id') : collect();

        $out = [];
        foreach ($d as $key => $l) {
            $pid = (int) $l['producto_id'];
            if (! isset($productos[$pid])) continue;
            $p = $productos[$pid];
            $v = ($l['variante_id'] ?? null) ? ($variantes[$l['variante_id']] ?? null) : null;
            $pvp = $v ? $v->pvp_final : (float) $p->precio;
            $label = $v ? $v->combo_label : '';
            $img = ($v && $v->foto_url) ? $v->foto_url : $p->imagen_principal;
            $out[] = [
                'key' => $key, 'producto' => $p, 'variante' => $v, 'label' => $label,
                'cantidad' => (int) $l['cantidad'], 'pvp' => round((float) $pvp, 2),
                'iva_rate' => (float) $p->iva_rate, 'img' => $img,
                'mto' => (bool) $p->bajo_pedido,
            ];
        }
        return $out;
    }

    public static function totales(): array
    {
        $sub = 0; $iva = 0; $tot = 0; $items = 0;
        foreach (self::lineas() as $l) {
            $neto = $l['pvp'] / (1 + $l['iva_rate'] / 100);
            $sub += $neto * $l['cantidad'];
            $iva += ($l['pvp'] - $neto) * $l['cantidad'];
            $tot += $l['pvp'] * $l['cantidad'];
            $items += $l['cantidad'];
        }
        return ['subtotal' => round($sub, 2), 'iva' => round($iva, 2), 'total' => round($tot, 2), 'items' => $items];
    }

    public static function cantidadItems(): int { return array_sum(array_map(fn ($l) => (int) $l['cantidad'], self::raw())); }
}
