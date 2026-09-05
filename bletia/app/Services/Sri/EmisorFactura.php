<?php

namespace App\Services\Sri;

use App\Models\SriComprobante;
use Illuminate\Support\Facades\DB;

/** Orquesta el ciclo completo de emisión de una FACTURA. */
class EmisorFactura
{
    /**
     * @param array $venta [
     *   'pedido_id','cliente_id',
     *   'comprador'=>['tipo_id','identificacion','razon','direccion','email','telefono'],
     *   'items'=>[['codigo','descripcion','cantidad','precio_unitario','descuento','iva_rate']],
     *   'forma_pago','origen'
     * ]
     */
    public static function emitir(array $venta): array
    {
        $chk = Emisor::completo();
        if (! $chk['ok']) return ['ok' => false, 'msg' => 'Configuración del emisor incompleta: ' . implode(', ', $chk['faltan'])];

        $fecha = date('d/m/Y');
        $secuencial = Secuencial::siguiente('01', Emisor::estab(), Emisor::ptoEmi());
        $clave = ClaveAcceso::generar($fecha, '01', Emisor::ruc(), Emisor::ambiente(), Emisor::estab(), Emisor::ptoEmi(), $secuencial, Emisor::tipoEmision());

        // calcular totales desde los items (para guardarlos en el comprobante)
        $totFact = ['subtotal' => 0.0, 'iva' => 0.0, 'total' => 0.0];
        foreach (($venta['items'] ?? []) as $d) {
            $cant = (float) ($d['cantidad'] ?? 1);
            $pu = (float) ($d['precio_unitario'] ?? 0);
            $desc = (float) ($d['descuento'] ?? 0);
            $baseItem = ($cant * $pu) - $desc;
            $rate = (float) ($d['iva_rate'] ?? 15);
            $totFact['subtotal'] += $baseItem;
            $totFact['iva'] += round($baseItem * $rate / 100, 2);
        }
        $totFact['subtotal'] = round($totFact['subtotal'], 2);
        $totFact['iva'] = round($totFact['iva'], 2);
        $totFact['total'] = round($totFact['subtotal'] + $totFact['iva'], 2);
        // crear registro
        $comp = SriComprobante::create([
            'tipo' => 'factura', 'cod_doc' => '01', 'ambiente' => Emisor::ambiente(),
            'estab' => Emisor::estab(), 'pto_emi' => Emisor::ptoEmi(), 'secuencial' => $secuencial,
            'clave_acceso' => $clave, 'estado' => 'CREADO',
            'pedido_id' => $venta['pedido_id'] ?? null, 'cliente_id' => $venta['cliente_id'] ?? null,
            'receptor_tipo_id' => $venta['comprador']['tipo_id'], 'receptor_identificacion' => $venta['comprador']['identificacion'],
            'receptor_razon' => $venta['comprador']['razon'], 'receptor_email' => $venta['comprador']['email'] ?? null,
            'receptor_direccion' => $venta['comprador']['direccion'] ?? null, 'receptor_telefono' => $venta['comprador']['telefono'] ?? null,
            'detalles' => $venta['items'],
            'subtotal' => $totFact['subtotal'], 'iva' => $totFact['iva'], 'total' => $totFact['total'],
        ]);
        self::log($comp->id, 'clave', 'OK', $clave);

        // XML
        try {
            $xml = XmlFactura::generar(array_merge($venta, ['clave_acceso' => $clave, 'secuencial' => $secuencial, 'fecha' => $fecha]));
            self::log($comp->id, 'xml', 'OK', 'generado');
        } catch (\Throwable $e) {
            $comp->update(['estado' => 'ERROR']); self::log($comp->id, 'xml', 'ERROR', $e->getMessage());
            return ['ok' => false, 'msg' => 'Error generando XML: ' . $e->getMessage(), 'comprobante_id' => $comp->id];
        }

        // Firma
        $f = Firma::firmar($xml, Emisor::p12Path(), Emisor::p12Pass(), 'factura');
        if (! $f['ok']) { $comp->update(['estado' => 'ERROR']); self::log($comp->id, 'firma', 'ERROR', $f['msg']); return ['ok' => false, 'msg' => 'Error al firmar: ' . $f['msg'], 'comprobante_id' => $comp->id]; }
        $xmlFirmado = $f['xml'];
        $comp->update(['estado' => 'FIRMADO', 'xml_firmado' => $xmlFirmado]);
        self::log($comp->id, 'firma', 'OK', 'vía ' . ($f['via'] ?? '?') . (isset($f['aviso']) ? ' | ' . $f['aviso'] : ''));

        // Recepción
        $rec = Ws::enviarRecepcion($xmlFirmado);
        if (! $rec['ok']) {
            $comp->update(['estado' => 'DEVUELTA']);
            self::log($comp->id, 'recepcion', $rec['estado'], self::msgsTxt($rec['mensajes']));
            return ['ok' => false, 'msg' => 'SRI devolvió el comprobante: ' . self::msgsTxt($rec['mensajes']), 'comprobante_id' => $comp->id];
        }
        $comp->update(['estado' => 'RECIBIDA']);
        self::log($comp->id, 'recepcion', 'RECIBIDA', 'ok');

        // Autorización (puede tardar; reintentar)
        $aut = null;
        for ($i = 0; $i < 4; $i++) {
            usleep(($i === 0 ? 1500000 : 2500000)); // 1.5s, luego 2.5s
            $aut = Ws::consultarAutorizacion($clave);
            if (in_array($aut['estado'], ['AUTORIZADO', 'NO AUTORIZADO', 'RECHAZADA'], true)) break;
        }
        if (! $aut || ! $aut['ok']) {
            $estado = $aut['estado'] ?? 'SIN_AUTORIZACION';
            $comp->update(['estado' => $estado === 'AUTORIZADO' ? 'AUTORIZADO' : 'NO_AUTORIZADO']);
            self::log($comp->id, 'autorizacion', $estado, self::msgsTxt($aut['mensajes'] ?? []));
            return ['ok' => false, 'msg' => 'No autorizado: ' . self::msgsTxt($aut['mensajes'] ?? []), 'comprobante_id' => $comp->id, 'estado' => $estado];
        }

        $comp->update([
            'estado' => 'AUTORIZADO',
            'numero_autorizacion' => $aut['numeroAutorizacion'] ?: $clave,
            'fecha_autorizacion' => $aut['fechaAutorizacion'] ? date('Y-m-d H:i:s', strtotime($aut['fechaAutorizacion'])) : now(),
            'xml_autorizado' => self::xmlAutorizado($xmlFirmado, $aut),
        ]);
        self::log($comp->id, 'autorizacion', 'AUTORIZADO', $aut['numeroAutorizacion'] ?? $clave);

        return ['ok' => true, 'msg' => 'Factura autorizada.', 'comprobante_id' => $comp->id, 'clave' => $clave, 'autorizacion' => $aut['numeroAutorizacion'] ?? $clave];
    }

    protected static function xmlAutorizado(string $xmlFirmado, array $aut): string
    {
        // envoltura estándar de comprobante autorizado
        $fecha = $aut['fechaAutorizacion'] ?? date('c');
        $num = $aut['numeroAutorizacion'] ?? '';
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<autorizacion><estado>AUTORIZADO</estado>'
            . '<numeroAutorizacion>' . $num . '</numeroAutorizacion>'
            . '<fechaAutorizacion>' . $fecha . '</fechaAutorizacion>'
            . '<ambiente>' . (Emisor::ambiente() === '2' ? 'PRODUCCIÓN' : 'PRUEBAS') . '</ambiente>'
            . '<comprobante><![CDATA[' . $xmlFirmado . ']]></comprobante>'
            . '</autorizacion>';
    }

    protected static function log($compId, string $paso, string $resultado, ?string $mensaje = null): void
    {
        try { DB::table('sri_logs')->insert(['comprobante_id' => $compId, 'paso' => $paso, 'resultado' => $resultado, 'mensaje' => $mensaje ? mb_substr($mensaje, 0, 2000) : null, 'created_at' => now(), 'updated_at' => now()]); } catch (\Throwable $e) {}
    }
    protected static function msgsTxt(array $msgs): string
    {
        return implode(' · ', array_map(fn ($m) => trim(($m['mensaje'] ?? '') . ' ' . ($m['info'] ?? '')), $msgs)) ?: 'sin detalle';
    }
}
