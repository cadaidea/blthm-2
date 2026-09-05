<?php

namespace App\Services\Sri;

use App\Models\Pedido;

/** Mapea un Pedido del ERP a una factura SRI, emite y envía RIDE+correo. */
class FacturarPedido
{
    public static function facturar($pedido, string $formaPago = '01', ?array $pagos = null, ?string $infoAdicional = null): array
    {
        if ($pedido->nro_factura) {
            return ['ok' => false, 'msg' => 'Este pedido ya tiene factura: ' . $pedido->nro_factura];
        }

        $cliente = $pedido->cliente;
        if (! $cliente) return ['ok' => false, 'msg' => 'El pedido no tiene cliente asignado.'];

        $comprador = self::mapearComprador($cliente);

        $items = [];
        foreach ($pedido->items as $it) {
            $cant = (float) ($it->cantidad ?: 1);
            // precio en DB es PVP con IVA incluido -> separar base
            $rate = (float) ($it->iva_rate ?? 15);
            $pvp = (float) ($it->precio ?: 0);
            $valorAdic = (float) ($it->valor_adicional ?? 0);
            $pvpTotal = $pvp + $valorAdic;
            // precio unitario sin IVA
            $puSinIva = $rate > 0 ? round($pvpTotal / (1 + $rate / 100), 6) : $pvpTotal;
            $descPct = (float) ($it->descuento_pct ?? 0);
            $descuento = round($puSinIva * $cant * $descPct / 100, 2);
            $items[] = [
                'codigo' => (string) ($it->producto_id ?: $it->id),
                'descripcion' => \App\Services\Sri\DetalleItem::descripcion($it),
                'cantidad' => $cant,
                'precio_unitario' => $puSinIva,
                'descuento' => $descuento,
                'iva_rate' => $rate,
            ];
        }
        if (empty($items)) return ['ok' => false, 'msg' => 'El pedido no tiene ítems.'];

        $venta = [
            'pedido_id' => $pedido->id,
            'cliente_id' => $cliente->id,
            'comprador' => $comprador,
            'items' => $items,
            'forma_pago' => $formaPago,
            'pagos' => $pagos ?: \App\Services\Sri\FormasPago::dePedido($pedido) ?: null,
            'origen' => 'erp',
            'info_adicional' => $infoAdicional,
        ];

        $r = EmisorFactura::emitir($venta);
        if (! ($r['ok'] ?? false)) return $r;

        // marcar pedido como facturado
        $comp = \App\Models\SriComprobante::find($r['comprobante_id'] ?? null);
        $numero = $comp ? ($comp->estab . '-' . $comp->pto_emi . '-' . $comp->secuencial) : null;
        $pedido->update(['nro_factura' => $numero, 'facturado_at' => now()]);

        // RIDE + correo
        if ($comp) {
            $env = EnviarComprobante::procesar($comp->fresh(), true);
            $r['ride'] = $env['msg'] ?? '';
        }
        $r['numero'] = $numero;
        return $r;
    }

    protected static function mapearComprador($cliente): array
    {
        $ident = $cliente->cedula_ruc ?: $cliente->identificacion ?: '';
        $tipo = $cliente->tipo_identificacion ?: $cliente->tipo_id ?: null;

        // normalizar a códigos SRI: 04=RUC, 05=CÉDULA, 06=PASAPORTE, 07=CONSUMIDOR FINAL
        $tipoId = match (true) {
            in_array($tipo, ['04', '05', '06', '07'], true) => $tipo,
            in_array(strtolower((string) $tipo), ['ruc'], true) => '04',
            in_array(strtolower((string) $tipo), ['cedula', 'cédula'], true) => '05',
            in_array(strtolower((string) $tipo), ['pasaporte'], true) => '06',
            strlen($ident) === 13 => '04',
            strlen($ident) === 10 => '05',
            default => '06',
        };

        $razon = $cliente->nombre ?: 'CONSUMIDOR FINAL';
        // consumidor final si no hay identificación válida
        if (! $ident || $razon === 'CONSUMIDOR FINAL') {
            return ['tipo_id' => '07', 'identificacion' => '9999999999999', 'razon' => 'CONSUMIDOR FINAL',
                'direccion' => $cliente->direccion ?: 'Cuenca', 'email' => $cliente->email, 'telefono' => $cliente->telefono ?: $cliente->celular];
        }

        return [
            'tipo_id' => $tipoId,
            'identificacion' => $ident,
            'razon' => $razon,
            'direccion' => $cliente->direccion ?: ($cliente->ciudad ?: 'Cuenca'),
            'email' => $cliente->email,
            'telefono' => $cliente->telefono ?: $cliente->celular,
        ];
    }
}
