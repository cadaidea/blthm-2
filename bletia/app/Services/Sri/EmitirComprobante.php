<?php

namespace App\Services\Sri;

use App\Models\Pedido;
use App\Models\SriComprobante;
use App\Models\Venta;
use App\Services\Folios;
use Illuminate\Support\Facades\DB;

/**
 * Emisor unificado de comprobantes de venta.
 * Sirve para los dos canales (pedido a fabricar y venta de stock) y los dos tipos:
 *  - factura    -> se emite al SRI (clave, firma, autorización) + RIDE + correo.
 *  - nota_venta -> documento interno VEN-XXXXXX + PDF + correo. No va al SRI.
 *
 * En ambos casos deja registro en `ventas` y marca el pedido.
 */
class EmitirComprobante
{
    /**
     * @param $pedido
     * @param string $tipo  'factura' | 'nota_venta'
     * @param string $formaPago  código SRI de forma de pago (01,20,19,17,16...)
     */
    public static function emitir($pedido, string $tipo, string $formaPago = '01', ?array $pagos = null, ?string $infoAdicional = null, ?array $credito = null): array
    {
        if ($pedido->nro_factura || ($pedido->venta_id ?? null)) {
            // ya tiene comprobante
            $vExist = Venta::where('pedido_id', $pedido->id)->first();
            if ($vExist) return ['ok' => false, 'msg' => 'Este pedido ya tiene comprobante: ' . ($vExist->numero_comprobante ?: $vExist->nro_factura), 'venta_id' => $vExist->id];
        }

        return $tipo === 'factura'
            ? self::emitirFactura($pedido, $formaPago, $pagos, $infoAdicional, $credito)
            : self::emitirNotaVenta($pedido, $formaPago, $infoAdicional, $credito);
    }

    /** FACTURA: usa el flujo SRI completo ya construido. */
    protected static function emitirFactura($pedido, string $formaPago, ?array $pagos = null, ?string $infoAdicional = null, ?array $credito = null): array
    {
        $esCredito = (bool) ($credito['es_credito'] ?? false);
        $plazoDias = $credito['plazo_dias'] ?? null;
        $total = round((float) \Illuminate\Support\Facades\DB::table('pedido_items')->where('pedido_id', $pedido->id)->sum('subtotal'), 2);
        if ($esCredito) {
            $pagos = \App\Services\Sri\Credito::pagosConCredito($pedido, $total, true, $plazoDias);
        }
        $r = FacturarPedido::facturar($pedido->fresh(), $formaPago, $pagos, $infoAdicional);
        if (! ($r['ok'] ?? false)) return $r;

        // registrar/actualizar venta enlazada al comprobante SRI
        $comp = SriComprobante::find($r['comprobante_id'] ?? null);
        if ($comp) {
            $ventaFac = self::registrarVenta($pedido, 'factura', $comp->estab . '-' . $comp->pto_emi . '-' . $comp->secuencial, $comp->id, $comp->total, null, null, $infoAdicional);
            if ($esCredito && $ventaFac) {
                $pagado = \App\Services\Sri\FormasPago::totalPagado($pedido);
                $saldo = round($comp->total - $pagado, 2);
                $ventaFac->update([
                    'es_credito' => true,
                    'credito_plazo_dias' => (int) ($plazoDias ?? 30),
                    'credito_vence_at' => \App\Services\Sri\Credito::vencimiento((int) ($plazoDias ?? 30)),
                    'saldo_credito' => max(0, $saldo),
                ]);
            }
        }
        return $r;
    }

    /** NOTA DE VENTA: documento interno, no SRI. */
    protected static function emitirNotaVenta($pedido, string $formaPago, ?string $infoAdicional = null, ?array $credito = null): array
    {
        $cliente = $pedido->cliente;

        // calcular totales desde los items del pedido
        $total = round((float) DB::table('pedido_items')->where('pedido_id', $pedido->id)->sum('subtotal'), 2);
        $base = round($total / 1.15, 2);
        $iva = round($total - $base, 2);

        $numero = Folios::next('VEN');

        $venta = self::registrarVenta($pedido, 'nota_venta', $numero, null, $total, $base, $iva, $infoAdicional);

        // crédito en nota de venta (registro interno para seguimiento de cobro)
        $esCredito = (bool) ($credito['es_credito'] ?? false);
        $plazoDias = $credito['plazo_dias'] ?? null;
        if ($esCredito && $venta) {
            $pagado = \App\Services\Sri\FormasPago::totalPagado($pedido);
            $saldo = round($total - $pagado, 2);
            $venta->update([
                'es_credito' => true,
                'credito_plazo_dias' => (int) ($plazoDias ?? 30),
                'credito_vence_at' => \App\Services\Sri\Credito::vencimiento((int) ($plazoDias ?? 30)),
                'saldo_credito' => max(0, $saldo),
            ]);
        }

        // marcar el pedido
        $pedido->update(['nro_factura' => $numero, 'facturado_at' => now()]);

        // generar PDF interno de la nota de venta + correo
        $aviso = '';
        try {
            $pdf = NotaVenta::generar($venta->fresh());
            if ($cliente && $cliente->email && filter_var($cliente->email, FILTER_VALIDATE_EMAIL)) {
                NotaVenta::enviar($venta->fresh(), $pdf);
                $aviso = 'Nota de venta enviada a ' . $cliente->email;
            } else {
                $aviso = 'Nota de venta generada (cliente sin correo).';
            }
        } catch (\Throwable $e) {
            $aviso = 'Nota generada; PDF/correo falló: ' . $e->getMessage();
        }

        return ['ok' => true, 'numero' => $numero, 'tipo' => 'nota_venta', 'venta_id' => $venta->id, 'ride' => $aviso];
    }

    /** Inserta/actualiza el registro maestro en `ventas`. */
    protected static function registrarVenta($pedido, string $tipo, string $numero, ?int $sriId, float $total, ?float $base = null, ?float $iva = null, ?string $infoAdicional = null): Venta
    {
        if (is_null($base)) { $base = round($total / 1.15, 2); $iva = round($total - $base, 2); }

        $venta = Venta::updateOrCreate(
            ['pedido_id' => $pedido->id],
            [
                'tipo_comprobante' => $tipo,
                'numero_comprobante' => $numero,
                'nro_factura' => $numero,
                'folio' => $numero,
                'sri_comprobante_id' => $sriId,
                'fecha' => now()->toDateString(),
                'cliente_id' => $pedido->cliente_id,
                'local_id' => $pedido->local_id ?? null,
                'vendedor_id' => $pedido->vendedor_id ?? auth()->id(),
                'forma_venta' => $pedido->forma_venta,
                'origen' => $pedido->origen ?? null,
                'codigo_origen' => $pedido->codigo_origen ?? null,
                'subtotal' => $base, 'iva' => $iva, 'total' => $total,
                'estado' => 'emitida',
                'info_adicional' => $infoAdicional,
                'facturado_por' => auth()->id(),
                'facturado_at' => now(),
            ]
        );
        return $venta;
    }
}
