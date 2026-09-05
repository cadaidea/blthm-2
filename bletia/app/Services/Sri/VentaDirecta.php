<?php

namespace App\Services\Sri;

use App\Models\Cliente;
use App\Models\SriComprobante;
use App\Models\Venta;
use App\Services\Folios;
use Illuminate\Support\Facades\DB;

/**
 * Línea de VENTA DE STOCK independiente de "pedidos" (fabricación).
 * No crea ni depende de ningún registro en la tabla `pedidos`.
 * Reutiliza el mismo motor SRI (EmisorFactura::emitir, misma secuencia y configuración)
 * y el mismo folio VEN para nota de venta, sin tocar el flujo existente de pedidos.
 *
 * @param array $venta [
 *   'cliente_id', 'items' => [['producto_id','nombre','cantidad','precio_unitario_con_iva','iva_rate']],
 *   'tipo' => 'factura'|'nota_venta', 'forma_pago', 'pagos', 'info_adicional', 'local_id'
 * ]
 */
class VentaDirecta
{
    public static function emitir(array $venta): array
    {
        $cliente = Cliente::find($venta['cliente_id'] ?? null);
        if (! $cliente) return ['ok' => false, 'msg' => 'Cliente no encontrado.'];

        $itemsIn = $venta['items'] ?? [];
        if (empty($itemsIn)) return ['ok' => false, 'msg' => 'Agrega al menos un ítem.'];

        // armar items en formato SRI (base sin IVA) + calcular total
        $itemsSri = []; $totalConIva = 0.0;
        foreach ($itemsIn as $it) {
            $cant = (float) ($it['cantidad'] ?: 1);
            $rate = (float) ($it['iva_rate'] ?? 15);
            $pvpUnit = (float) ($it['precio_unitario_con_iva'] ?? 0);
            $puSinIva = $rate > 0 ? round($pvpUnit / (1 + $rate / 100), 6) : $pvpUnit;
            $sub = round($pvpUnit * $cant, 2);
            $totalConIva += $sub;
            $itemsSri[] = [
                'codigo' => (string) ($it['producto_id'] ?? 'ITEM'),
                'descripcion' => $it['nombre'] ?? 'Producto',
                'cantidad' => $cant,
                'precio_unitario' => $puSinIva,
                'descuento' => 0,
                'iva_rate' => $rate,
            ];
        }
        $totalConIva = round($totalConIva, 2);

        $tipo = $venta['tipo'] ?? 'factura';

        if ($tipo === 'factura') {
            return self::emitirFactura($cliente, $itemsSri, $totalConIva, $venta);
        }
        return self::emitirNotaVenta($cliente, $itemsSri, $totalConIva, $venta);
    }

    protected static function emitirFactura(Cliente $cliente, array $itemsSri, float $total, array $venta): array
    {
        $comprador = self::mapearComprador($cliente);

        $datosVenta = [
            'pedido_id' => null,
            'cliente_id' => $cliente->id,
            'comprador' => $comprador,
            'items' => $itemsSri,
            'forma_pago' => $venta['forma_pago'] ?? '01',
            'pagos' => $venta['pagos_xml'] ?? null, // se arma luego de crear recibos si no viene precalculado
            'origen' => 'venta_directa',
            'info_adicional' => $venta['info_adicional'] ?? null,
        ];

        // mismo núcleo de emisión SRI que ya usan los pedidos (misma secuencia, misma config)
        $r = EmisorFactura::emitir($datosVenta);
        if (! ($r['ok'] ?? false)) return $r;

        $comp = SriComprobante::find($r['comprobante_id'] ?? null);
        $numero = $comp ? ($comp->estab . '-' . $comp->pto_emi . '-' . $comp->secuencial) : null;

        $registroVenta = self::registrarVenta($cliente, 'factura', $numero, $comp?->id, $comp?->total ?? $total, null, null, $venta);
        self::crearRecibos($registroVenta, $venta['pagos_captura'] ?? []);

        if ($comp) {
            $env = EnviarComprobante::procesar($comp->fresh(), true);
            $r['ride'] = $env['msg'] ?? '';
        }
        $r['numero'] = $numero;
        $r['venta_id'] = $registroVenta->id;
        return $r;
    }

    protected static function emitirNotaVenta(Cliente $cliente, array $itemsSri, float $total, array $venta): array
    {
        $base = round($total / 1.15, 2);
        $iva = round($total - $base, 2);
        $numero = Folios::next('VEN');

        $registroVenta = self::registrarVenta($cliente, 'nota_venta', $numero, null, $total, $base, $iva, $venta, $itemsSri);
        self::crearRecibos($registroVenta, $venta['pagos_captura'] ?? []);

        $aviso = '';
        try {
            $pdf = NotaVentaDirecta::generar($registroVenta->fresh(), $itemsSri);
            if ($cliente->email && filter_var($cliente->email, FILTER_VALIDATE_EMAIL)) {
                NotaVentaDirecta::enviar($registroVenta->fresh(), $pdf);
                $aviso = 'Nota de venta enviada a ' . $cliente->email;
            } else {
                $aviso = 'Nota de venta generada (cliente sin correo).';
            }
        } catch (\Throwable $e) {
            $aviso = 'Nota generada; PDF/correo falló: ' . $e->getMessage();
        }

        return ['ok' => true, 'numero' => $numero, 'tipo' => 'nota_venta', 'venta_id' => $registroVenta->id, 'ride' => $aviso];
    }

    /** Inserta el registro maestro en `ventas`, SIN pedido_id (venta directa de stock). */
    protected static function registrarVenta(Cliente $cliente, string $tipo, string $numero, ?int $sriId, float $total, ?float $base = null, ?float $iva = null, array $venta = [], array $itemsParaNota = []): Venta
    {
        if (is_null($base)) { $base = round($total / 1.15, 2); $iva = round($total - $base, 2); }

        return Venta::create([
            'pedido_id' => null,
            'tipo_comprobante' => $tipo,
            'numero_comprobante' => $numero,
            'nro_factura' => $numero,
            'folio' => $numero,
            'sri_comprobante_id' => $sriId,
            'fecha' => now()->toDateString(),
            'cliente_id' => $cliente->id,
            'local_id' => $venta['local_id'] ?? (auth()->user()->local_id ?? null),
            'vendedor_id' => auth()->id(),
            'forma_venta' => 'stock',
            'origen' => 'venta_directa',
            'codigo_origen' => null,
            'subtotal' => $base, 'iva' => $iva, 'total' => $total,
            'estado' => 'emitida',
            'info_adicional' => $venta['info_adicional'] ?? null,
            'facturado_por' => auth()->id(),
            'facturado_at' => now(),
        ]);
    }

    protected static function mapearComprador(Cliente $cliente): array
    {
        $ident = $cliente->cedula_ruc ?: $cliente->identificacion ?: '';
        $tipo = $cliente->tipo_identificacion ?: $cliente->tipo_id ?: null;
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
        if (! $ident || $razon === 'CONSUMIDOR FINAL') {
            return ['tipo_id' => '07', 'identificacion' => '9999999999999', 'razon' => 'CONSUMIDOR FINAL',
                'direccion' => $cliente->direccion ?: 'Cuenca', 'email' => $cliente->email, 'telefono' => $cliente->telefono ?: $cliente->celular];
        }
        return [
            'tipo_id' => $tipoId, 'identificacion' => $ident, 'razon' => $razon,
            'direccion' => $cliente->direccion ?: ($cliente->ciudad ?: 'Cuenca'),
            'email' => $cliente->email, 'telefono' => $cliente->telefono ?: $cliente->celular,
        ];
    }

    /**
     * Crea los recibos de pago capturados en el formulario de venta directa.
     * Efectivo y tarjeta quedan auto-validados (igual que en pedidos). Transferencia/depósito/cheque
     * quedan pendientes de validar por contabilidad/operaciones.
     * @param array $pagos [['metodo','monto','tipo_tarjeta','tarjeta_naturaleza','cheque_numero','cheque_banco','cheque_fecha_cobro','nro_comprobante']]
     */
    public static function crearRecibos(Venta $venta, array $pagos): void
    {
        foreach ($pagos as $p) {
            $metodo = strtolower((string) ($p['metodo'] ?? 'efectivo'));
            $autoValidar = in_array($metodo, ['efectivo', 'tarjeta'], true);
            \App\Models\Recibo::create([
                'venta_id'    => $venta->id,
                'cliente_id'  => $venta->cliente_id,
                'tipo'        => 'cobro',
                'monto'       => (float) ($p['monto'] ?? 0),
                'metodo'      => $metodo,
                'fecha'       => now()->toDateString(),
                'validado'    => $autoValidar,
                'validado_por'=> $autoValidar ? auth()->id() : null,
                'validado_at' => $autoValidar ? now() : null,
                'tipo_tarjeta'       => $p['tipo_tarjeta'] ?? null,
                'tarjeta_naturaleza' => $p['tarjeta_naturaleza'] ?? null,
                'cheque_girador'     => $p['cheque_girador'] ?? null,
                'cheque_numero'      => $p['cheque_numero'] ?? null,
                'cheque_banco'       => $p['cheque_banco'] ?? null,
                'cheque_fecha_cobro' => $p['cheque_fecha_cobro'] ?? null,
                'cheque_estado'      => 'pendiente',
                'nro_comprobante'    => $p['nro_comprobante'] ?? null,
                'comprobantes'       => $p['comprobantes'] ?? null,
                'nota'        => 'Cobro en venta directa de stock · ' . ($venta->numero_comprobante ?: $venta->folio),
            ]);
        }
    }
}
