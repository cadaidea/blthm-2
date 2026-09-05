<?php

namespace App\Services\Sri;

use App\Models\SriComprobante;
use Illuminate\Support\Facades\DB;

/** Orquesta el ciclo completo de emisión de una GUÍA DE REMISIÓN electrónica (codDoc 06). */
class EmisorGuiaRemision
{
    /**
     * @param array $g [
     *   'pedido_id','cliente_id','despacho_id',
     *   'dir_partida',
     *   'transportista'=>['razon','ruc','tipo_id','placa'],
     *   'fecha_ini','fecha_fin',
     *   'destinatario'=>['razon','identificacion','direccion','motivo','ruta','doc_sustento'=>[...]],
     *   'items'=>[...],
     *   'info_adicional'=>[...]
     * ]
     */
    public static function emitir(array $g): array
    {
        $chk = Emisor::completo();
        if (! $chk['ok']) return ['ok' => false, 'msg' => 'Configuración del emisor incompleta: ' . implode(', ', $chk['faltan'])];

        $fecha = date('d/m/Y');
        $secuencial = Secuencial::siguiente('06', Emisor::estab(), Emisor::ptoEmi());
        $clave = ClaveAcceso::generar($fecha, '06', Emisor::ruc(), Emisor::ambiente(), Emisor::estab(), Emisor::ptoEmi(), $secuencial, Emisor::tipoEmision());

        $comp = SriComprobante::create([
            'tipo' => 'guia_remision', 'cod_doc' => '06', 'ambiente' => Emisor::ambiente(),
            'estab' => Emisor::estab(), 'pto_emi' => Emisor::ptoEmi(), 'secuencial' => $secuencial,
            'clave_acceso' => $clave, 'estado' => 'CREADO',
            'pedido_id' => $g['pedido_id'] ?? null, 'cliente_id' => $g['cliente_id'] ?? null,
            'receptor_identificacion' => $g['destinatario']['identificacion'] ?? null,
            'receptor_razon' => $g['destinatario']['razon'] ?? null,
            'receptor_direccion' => $g['destinatario']['direccion'] ?? null,
            'detalles' => $g['items'],
            'extra' => [
                'despacho_id' => $g['despacho_id'] ?? null,
                'transportista' => $g['transportista'],
                'fecha_ini' => $g['fecha_ini'], 'fecha_fin' => $g['fecha_fin'],
                'motivo' => $g['destinatario']['motivo'] ?? null,
                'info_adicional' => $g['info_adicional'] ?? [],
            ],
        ]);
        self::log($comp->id, 'clave', 'OK', $clave);

        try {
            // Res. NAC-DGERCGC26-00000027: RUC del proveedor del sistema de facturacion (autoconsumo Bletia).
            // Se agrega sin pisar cualquier info_adicional que ya venga del llamador.
            $infoAdic = array_merge($g['info_adicional'] ?? [], ['proveedorFacturacion' => Emisor::ruc()]);
            $xml = XmlGuiaRemision::generar(array_merge($g, ['clave_acceso' => $clave, 'secuencial' => $secuencial, 'fecha' => $fecha, 'info_adicional' => $infoAdic]));
            self::log($comp->id, 'xml', 'OK', 'generado');
        } catch (\Throwable $e) {
            $comp->update(['estado' => 'ERROR']); self::log($comp->id, 'xml', 'ERROR', $e->getMessage());
            return ['ok' => false, 'msg' => 'Error generando XML: ' . $e->getMessage(), 'comprobante_id' => $comp->id];
        }

        // Firma (tipo guia_remision → microservicio usa signDeliveryGuideXml)
        $f = Firma::firmar($xml, Emisor::p12Path(), Emisor::p12Pass(), 'guia_remision');
        if (! $f['ok']) { $comp->update(['estado' => 'ERROR']); self::log($comp->id, 'firma', 'ERROR', $f['msg']); return ['ok' => false, 'msg' => 'Error al firmar: ' . $f['msg'], 'comprobante_id' => $comp->id]; }
        $xmlFirmado = $f['xml'];
        $comp->update(['estado' => 'FIRMADO', 'xml_firmado' => $xmlFirmado]);
        self::log($comp->id, 'firma', 'OK', 'vía ' . ($f['via'] ?? '?'));

        // Recepción
        $rec = Ws::enviarRecepcion($xmlFirmado);
        if (! $rec['ok']) {
            $comp->update(['estado' => 'DEVUELTA']);
            self::log($comp->id, 'recepcion', $rec['estado'], self::msgsTxt($rec['mensajes']));
            return ['ok' => false, 'msg' => 'SRI devolvió la guía: ' . self::msgsTxt($rec['mensajes']), 'comprobante_id' => $comp->id];
        }
        $comp->update(['estado' => 'RECIBIDA']);
        self::log($comp->id, 'recepcion', 'RECIBIDA', 'ok');

        // Autorización
        $aut = null;
        for ($i = 0; $i < 4; $i++) {
            usleep(($i === 0 ? 1500000 : 2500000));
            $aut = Ws::consultarAutorizacion($clave);
            if (in_array($aut['estado'], ['AUTORIZADO', 'NO AUTORIZADO', 'RECHAZADA'], true)) break;
        }
        if (! $aut || ! $aut['ok']) {
            $estado = $aut['estado'] ?? 'SIN_AUTORIZACION';
            $comp->update(['estado' => $estado === 'AUTORIZADO' ? 'AUTORIZADO' : 'NO_AUTORIZADO']);
            self::log($comp->id, 'autorizacion', $estado, self::msgsTxt($aut['mensajes'] ?? []));
            return ['ok' => false, 'msg' => 'Guía no autorizada: ' . self::msgsTxt($aut['mensajes'] ?? []), 'comprobante_id' => $comp->id, 'estado' => $estado];
        }

        $comp->update([
            'estado' => 'AUTORIZADO',
            'numero_autorizacion' => $aut['numeroAutorizacion'] ?: $clave,
            'fecha_autorizacion' => $aut['fechaAutorizacion'] ? date('Y-m-d H:i:s', strtotime($aut['fechaAutorizacion'])) : now(),
            'xml_autorizado' => self::xmlAutorizado($xmlFirmado, $aut),
        ]);
        self::log($comp->id, 'autorizacion', 'AUTORIZADO', $aut['numeroAutorizacion'] ?? $clave);

        return ['ok' => true, 'msg' => 'Guía de remisión autorizada.', 'comprobante_id' => $comp->id, 'clave' => $clave,
            'numero' => Emisor::estab() . '-' . Emisor::ptoEmi() . '-' . str_pad($secuencial, 9, '0', STR_PAD_LEFT),
            'autorizacion' => $aut['numeroAutorizacion'] ?? $clave];
    }

    protected static function xmlAutorizado(string $xmlFirmado, array $aut): string
    {
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
