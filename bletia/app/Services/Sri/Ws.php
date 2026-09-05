<?php

namespace App\Services\Sri;

/** Cliente de los web services SOAP del SRI: recepción y autorización. */
class Ws
{
    /** Envía el XML firmado a recepción. Devuelve estado RECIBIDA/DEVUELTA + mensajes. */
    public static function enviarRecepcion(string $xmlFirmado): array
    {
        try {
            $client = new \SoapClient(Emisor::wsRecepcion(), [
                'trace' => 1, 'exceptions' => true, 'connection_timeout' => 30,
                'cache_wsdl' => WSDL_CACHE_NONE,
            ]);
            $resp = $client->validarComprobante(['xml' => $xmlFirmado]);
            $estado = $resp->RespuestaRecepcionComprobante->estado ?? 'DESCONOCIDO';
            $mensajes = self::extraerMensajes($resp->RespuestaRecepcionComprobante->comprobantes ?? null);
            return ['ok' => $estado === 'RECIBIDA', 'estado' => $estado, 'mensajes' => $mensajes, 'raw' => $resp];
        } catch (\Throwable $e) {
            return ['ok' => false, 'estado' => 'ERROR', 'mensajes' => [['tipo' => 'EXCEPCION', 'mensaje' => $e->getMessage()]]];
        }
    }

    /** Consulta autorización por clave de acceso. */
    public static function consultarAutorizacion(string $claveAcceso): array
    {
        try {
            $client = new \SoapClient(Emisor::wsAutorizacion(), [
                'trace' => 1, 'exceptions' => true, 'connection_timeout' => 30,
                'cache_wsdl' => WSDL_CACHE_NONE,
            ]);
            $resp = $client->autorizacionComprobante(['claveAccesoComprobante' => $claveAcceso]);
            $aut = $resp->RespuestaAutorizacionComprobante->autorizaciones->autorizacion ?? null;
            if (is_array($aut)) $aut = $aut[0];
            if (! $aut) return ['ok' => false, 'estado' => 'SIN_AUTORIZACION', 'mensajes' => [['mensaje' => 'Sin respuesta de autorización']]];
            $estado = $aut->estado ?? 'DESCONOCIDO';
            return [
                'ok' => $estado === 'AUTORIZADO',
                'estado' => $estado,
                'numeroAutorizacion' => $aut->numeroAutorizacion ?? null,
                'fechaAutorizacion' => isset($aut->fechaAutorizacion) ? (string) $aut->fechaAutorizacion : null,
                'comprobante' => $aut->comprobante ?? null,
                'mensajes' => self::extraerMensajesAut($aut->mensajes ?? null),
                'raw' => $resp,
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'estado' => 'ERROR', 'mensajes' => [['tipo' => 'EXCEPCION', 'mensaje' => $e->getMessage()]]];
        }
    }

    protected static function extraerMensajes($comprobantes): array
    {
        $out = [];
        if (! $comprobantes) return $out;
        $comp = $comprobantes->comprobante ?? null;
        if (! $comp) return $out;
        $items = is_array($comp) ? $comp : [$comp];
        foreach ($items as $c) {
            $msgs = $c->mensajes->mensaje ?? null;
            if (! $msgs) continue;
            foreach ((is_array($msgs) ? $msgs : [$msgs]) as $m) {
                $out[] = ['identificador' => $m->identificador ?? '', 'mensaje' => $m->mensaje ?? '', 'info' => $m->informacionAdicional ?? '', 'tipo' => $m->tipo ?? ''];
            }
        }
        return $out;
    }

    protected static function extraerMensajesAut($mensajes): array
    {
        $out = [];
        if (! $mensajes) return $out;
        $m = $mensajes->mensaje ?? null;
        if (! $m) return $out;
        foreach ((is_array($m) ? $m : [$m]) as $x) {
            $out[] = ['identificador' => $x->identificador ?? '', 'mensaje' => $x->mensaje ?? '', 'info' => $x->informacionAdicional ?? '', 'tipo' => $x->tipo ?? ''];
        }
        return $out;
    }
}
