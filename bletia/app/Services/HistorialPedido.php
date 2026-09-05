<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Devuelve solo strings/scalars (sin objetos stdClass) para compatibilidad con Livewire.
 */
class HistorialPedido
{
    public static function de($pedidoId): array
    {
        $p = DB::table('pedidos')->where('id', $pedidoId)->first();
        if (! $p) return [];

        $userName = fn ($id) => $id ? (DB::table('users')->where('id', $id)->value('name') ?: null) : null;

        // fabricante
        $fabricante = '—'; $fabricanteTipo = null;
        $proveedorEmail = null; $proveedorNombre = null; $proveedorRuc = null; $proveedorDireccion = null;
        if ($p->destino_fab === 'proveedor') {
            $provId = DB::table('pedido_items')->where('pedido_id', $p->id)->whereNotNull('proveedor_id')->value('proveedor_id');
            if ($provId) {
                $prov = DB::table('proveedores')->where('id', $provId)->first();
                if ($prov) {
                    $fabricante = $prov->nombre ?? 'Proveedor';
                    $proveedorEmail = $prov->email ?? null;
                    $proveedorNombre = $prov->nombre ?? null;
                    $proveedorRuc = $prov->ruc ?? $prov->identificacion ?? null;
                    $proveedorDireccion = $prov->direccion ?? null;
                }
            }
            $fabricanteTipo = 'proveedor';
        } elseif (in_array($p->destino_fab, ['taller', 'interno'], true)) {
            $fabricante = 'Taller interno';
            $fabricanteTipo = 'taller';
        }

        // despacho / traslado
        $desp = DB::table('despachos')->where('pedido_id', $p->id)->latest('id')->first();
        $traslado = '—'; $trasladoTipo = null;
        $transportistaNombre = null; $transportistaCelular = null; $despachoid = null;
        if ($desp) {
            $despachoid = $desp->id;
            if ($desp->ruta === 'retiro_local') {
                $traslado = 'Retiro en local';
                $trasladoTipo = 'retiro';
            } else {
                $trasladoTipo = 'transportista';
                if ($desp->transportista_id) {
                    $t = DB::table('transportistas')->where('id', $desp->transportista_id)->first();
                    $transportistaNombre = $t ? ($t->empresa ?? $t->nombre ?? null) : null;
                    $transportistaCelular = $t ? ($t->celular ?? null) : null;
                    $traslado = ($transportistaNombre ?? 'Transportista') . ($desp->conductor_nombre ? ' · ' . $desp->conductor_nombre : '');
                } elseif ($desp->conductor_nombre) {
                    $traslado = $desp->conductor_nombre;
                }
            }
        }

        return [
            'folio'               => $p->folio ?: ('#' . $p->id),
            'pedido_id'           => (int) $p->id,
            'vendedor'            => $userName($p->vendido_por) ?? $userName($p->vendedor_id) ?? '—',
            'aprobado_por'        => $userName($p->aprobado_por) ?? '—',
            'fabricante'          => $fabricante,
            'fabricante_tipo'     => (string) ($fabricanteTipo ?? ''),
            'proveedor_nombre'    => (string) ($proveedorNombre ?? ''),
            'proveedor_email'     => (string) ($proveedorEmail ?? ''),
            'proveedor_ruc'       => (string) ($proveedorRuc ?? ''),
            'proveedor_direccion' => (string) ($proveedorDireccion ?? ''),
            'enviado_fab_por'     => $userName($p->enviado_fab_por) ?? '—',
            'traslado'            => $traslado,
            'traslado_tipo'       => (string) ($trasladoTipo ?? ''),
            'transportista_nombre'=> (string) ($transportistaNombre ?? ''),
            'transportista_cel'   => (string) ($transportistaCelular ?? ''),
            'despacho_id'         => $despachoid ? (int) $despachoid : null,
            'nro_factura'         => (string) ($p->nro_factura ?? ''),
            'fecha_entrega'       => $desp?->entregado_at ?? ($p->fecha_entrega ?? null),
            'recibido_por'        => (string) ($desp?->recibido_nombre ?? $p->nombre_recibe ?? ''),
        ];
    }
}
