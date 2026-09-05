<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

/**
 * Genera el PDF de etiquetas de bultos para un pedido.
 * Una etiqueta por bulto (suma de bultos de todos los items).
 * Muestra: datos del pedido + nombre, dirección y ciudad del cliente.
 * NUNCA datos de contacto (teléfono/email) ni precios.
 */
class Etiquetas
{
    public static function generar($pedido): string
    {
        $p = is_object($pedido) ? $pedido : \App\Models\PedidoEspecial::find($pedido);
        $items = DB::table('pedido_items')->where('pedido_id', $p->id)->get();
        $cli = $p->cliente_id ? DB::table('clientes')->where('id', $p->cliente_id)->first() : null;

        // construir lista de etiquetas: una por bulto de cada item
        $etiquetas = [];
        $totalBultos = 0;
        foreach ($items as $it) {
            $n = max(1, (int) ($it->bultos ?? 1));
            $totalBultos += $n;
        }
        $idx = 0;
        foreach ($items as $it) {
            $n = max(1, (int) ($it->bultos ?? 1));
            for ($i = 1; $i <= $n; $i++) {
                $idx++;
                $etiquetas[] = [
                    'item'        => $it->nombre,
                    'bulto_item'  => $i,
                    'bultos_item' => $n,
                    'indice'      => $idx,
                    'total'       => $totalBultos,
                    'tapiz'       => $it->tapiz_principal ?? null,
                    'lacado'      => $it->lacado ?? null,
                ];
            }
        }

        $datos = [
            'folio'     => $p->folio ?: ('#' . $p->id),
            'cliente'   => $cli->nombre ?? '—',
            'direccion' => $cli->direccion ?? '—',
            'ciudad'    => $cli->ciudad ?? ($cli->provincia ?? '—'),
            'fecha'     => now()->format('d/m/Y'),
        ];

        $html = view('sri.etiquetas', compact('etiquetas', 'datos'))->render();
        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'landscape');
        $dir = storage_path('app/etiquetas');
        if (! is_dir($dir)) @mkdir($dir, 0775, true);
        $path = $dir . '/ETIQUETAS_' . ($p->folio ?: $p->id) . '.pdf';
        $pdf->save($path);
        return $path;
    }

    /**
     * Genera etiquetas de GARANTÍA con un número de bultos específico (del reclamo, no del pedido).
     * Usa exactamente el mismo diseño que las etiquetas de pedidos (sri.etiquetas),
     * solo que el "producto" indica que es una devolución de garantía y el folio es el del reclamo.
     */
    public static function generarConBultos($pedido, int $bultos, ?string $folioReclamo = null, ?string $nombreProducto = null): string
    {
        $p = is_object($pedido) ? $pedido : \App\Models\PedidoEspecial::find($pedido);
        $cli = $p->cliente_id ? DB::table('clientes')->where('id', $p->cliente_id)->first() : null;
        $bultos = max(1, $bultos);

        $etiquetas = [];
        for ($i = 1; $i <= $bultos; $i++) {
            $etiquetas[] = [
                'item'        => 'GARANTÍA · ' . ($nombreProducto ?: 'Producto'),
                'bulto_item'  => $i,
                'bultos_item' => $bultos,
                'indice'      => $i,
                'total'       => $bultos,
                'tapiz'       => null,
                'lacado'      => null,
            ];
        }

        $datos = [
            'folio'     => $folioReclamo ?: ($p->folio ?: ('#' . $p->id)),
            'cliente'   => $cli->nombre ?? '—',
            'direccion' => $cli->direccion ?? '—',
            'ciudad'    => $cli->ciudad ?? ($cli->provincia ?? '—'),
            'fecha'     => now()->format('d/m/Y'),
        ];

        $html = view('sri.etiquetas', compact('etiquetas', 'datos'))->render();
        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'landscape');
        $dir = storage_path('app/etiquetas');
        if (! is_dir($dir)) @mkdir($dir, 0775, true);
        $path = $dir . '/ETIQUETAS-GARANTIA_' . ($folioReclamo ?: $p->id) . '.pdf';
        $pdf->save($path);
        return $path;
    }

    /**
     * Genera etiquetas para una COMPRA A PROVEEDOR (cada ítem con sus bultos confirmados).
     * Usa el mismo diseño que las etiquetas de pedidos (sri.etiquetas), mostrando el destino
     * (TU local/bodega, no un cliente) y el folio de la orden de compra.
     */
    public static function generarParaCompra($compra): string
    {
        $local = $compra->local_destino_id ? DB::table('locales')->where('id', $compra->local_destino_id)->first() : null;

        $etiquetas = [];
        $totalBultos = 0;
        foreach ($compra->items as $it) $totalBultos += max(1, (int) ($it->bultos ?? 1));

        $idx = 0;
        foreach ($compra->items as $it) {
            $n = max(1, (int) ($it->bultos ?? 1));
            for ($i = 1; $i <= $n; $i++) {
                $idx++;
                $etiquetas[] = [
                    'item' => $it->nombre, 'bulto_item' => $i, 'bultos_item' => $n,
                    'indice' => $idx, 'total' => $totalBultos, 'tapiz' => null, 'lacado' => null,
                ];
            }
        }

        $datos = [
            'folio'     => $compra->folio ?: ('#' . $compra->id),
            'cliente'   => $local->nombre ?? 'Bodega propia',
            'direccion' => $local->direccion ?? '—',
            'ciudad'    => $local->ciudad ?? '—',
            'fecha'     => now()->format('d/m/Y'),
        ];

        $html = view('sri.etiquetas', compact('etiquetas', 'datos'))->render();
        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'landscape');
        $dir = storage_path('app/etiquetas');
        if (! is_dir($dir)) @mkdir($dir, 0775, true);
        $path = $dir . '/ETIQUETAS-COMPRA_' . ($compra->folio ?: $compra->id) . '.pdf';
        $pdf->save($path);
        return $path;
    }
}
