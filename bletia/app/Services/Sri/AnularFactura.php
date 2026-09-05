<?php

namespace App\Services\Sri;

use App\Models\SriComprobante;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;

/**
 * Anula una factura emitiendo una Nota de Crédito que la reversa.
 * Conserva la factura original (nunca se elimina): queda con su NC asociada.
 */
class AnularFactura
{
    /**
     * Res. NAC-DGERCGC25-00000017: la anulacion en linea procede solo hasta el dia 7
     * del mes siguiente a la emision. Pasado eso, sigue procediendo la Nota de Credito
     * (sin limite de 12 meses), esto es solo informativo/no bloqueante.
     */
    public static function fueraDePlazoAnulacion($fechaEmision): bool
    {
        $f = \Illuminate\Support\Carbon::parse($fechaEmision);
        $limite = $f->copy()->addMonthNoOverflow()->startOfMonth()->addDays(6)->endOfDay();
        return now()->greaterThan($limite);
    }

    public static function porVenta(Venta $venta, string $motivo): array
    {
        if ($venta->tipo_comprobante !== 'factura') {
            return ['ok' => false, 'msg' => 'Esta venta no es una factura.'];
        }
        $factura = $venta->sriComprobante;
        if (! $factura || $factura->estado !== 'AUTORIZADO') {
            return ['ok' => false, 'msg' => 'La factura no está autorizada; no se puede emitir NC.'];
        }
        // El emisor asume la responsabilidad del dato "consumidor final": esa factura queda
        // fija, no procede anulación en línea ni Nota de Crédito sobre ella (normativa SRI vigente).
        if ((string) $factura->receptor_identificacion === '9999999999999') {
            return ['ok' => false, 'msg' => 'Esta factura fue emitida a consumidor final (9999999999999). No procede anulación en línea ni Nota de Crédito sobre facturas a consumidor final; el comprobante queda fijo.'];
        }
        // evitar doble NC
        $yaNC = SriComprobante::where('comprobante_ref_id', $factura->id)->where('cod_doc', '04')->where('estado', 'AUTORIZADO')->first();
        if ($yaNC) {
            return ['ok' => false, 'msg' => 'Esta factura ya tiene una nota de crédito autorizada.'];
        }

        // reconstruir ítems desde los detalles guardados en la factura
        $items = is_array($factura->detalles) ? $factura->detalles : (json_decode($factura->detalles ?? '[]', true) ?: []);
        if (! $items) {
            return ['ok' => false, 'msg' => 'La factura no tiene detalles para reversar.'];
        }

        $numFactura = $factura->estab . '-' . $factura->pto_emi . '-' . str_pad($factura->secuencial, 9, '0', STR_PAD_LEFT);
        $fechaFactura = \Illuminate\Support\Carbon::parse($factura->created_at)->format('d/m/Y');

        $nc = [
            'pedido_id' => $factura->pedido_id,
            'cliente_id' => $factura->cliente_id,
            'comprobante_ref_id' => $factura->id,
            'comprador' => [
                'tipo_id' => $factura->receptor_tipo_id,
                'identificacion' => $factura->receptor_identificacion,
                'razon' => $factura->receptor_razon,
                'direccion' => $factura->receptor_direccion,
                'email' => $factura->receptor_email,
                'telefono' => $factura->receptor_telefono,
            ],
            'items' => $items,
            'doc_modificado' => ['cod_doc' => '01', 'num' => $numFactura, 'fecha_emision' => $fechaFactura],
            'motivo' => $motivo,
        ];

        $r = EmisorNotaCredito::emitir($nc);
        if (! ($r['ok'] ?? false)) return $r;

        // marcar la venta como anulada (la factura original se conserva, ahora con NC)
        $venta->update(['estado' => 'anulada']);
        // liberar el pedido
        if ($venta->pedido_id) {
            DB::table('pedidos')->where('id', $venta->pedido_id)->update(['nro_factura' => null, 'facturado_at' => null]);
        }
        if (class_exists(\App\Models\Bitacora::class)) {
            \App\Models\Bitacora::registrar('anuló factura con NC', 'Venta', $venta->id, 'NC ' . ($r['numero'] ?? '') . ' · ' . $motivo);
        }

        // enviar la NC por correo al cliente
        try {
            $compNC = SriComprobante::find($r['comprobante_id']);
            if ($compNC) EnviarComprobante::procesar($compNC->fresh(), true);
        } catch (\Throwable $e) {}

        return ['ok' => true, 'msg' => 'Factura anulada con NC ' . ($r['numero'] ?? '') . '.', 'numero' => $r['numero'] ?? ''];
    }
}
