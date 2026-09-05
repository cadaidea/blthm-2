<?php

namespace App\Services\Sri;

use XMLWriter;

/**
 * Genera el XML de una GUÍA DE REMISIÓN electrónica (codDoc 06, versión 1.1.0).
 * Estructura oficial SRI: infoTributaria + infoGuiaRemision + destinatarios + infoAdicional.
 * Sin xmlns; root <guiaRemision id="comprobante"> para ec-sri-invoice-signer.
 */
class XmlGuiaRemision
{
    /**
     * @param array $g [
     *   'clave_acceso','secuencial','fecha',
     *   'dir_partida',
     *   'transportista'=>['razon','ruc','tipo_id','placa'],
     *   'fecha_ini','fecha_fin',
     *   'destinatario'=>['razon','identificacion','direccion','motivo','ruta','doc_sustento'=>['cod','num','fecha','num_aut']],
     *   'items'=>[ ['codigo','descripcion','cantidad'], ... ],
     *   'info_adicional'=>[clave=>valor]
     * ]
     */
    public static function generar(array $g): string
    {
        $w = new XMLWriter();
        $w->openMemory();
        $w->startDocument('1.0', 'UTF-8');

        $w->startElement('guiaRemision');
        $w->writeAttribute('id', 'comprobante');
        $w->writeAttribute('version', '1.1.0');

        // ===== infoTributaria =====
        $w->startElement('infoTributaria');
        self::el($w, 'ambiente', Emisor::ambiente());
        self::el($w, 'tipoEmision', Emisor::tipoEmision());
        self::el($w, 'razonSocial', Emisor::razon());
        if (Emisor::nombreComercial()) self::el($w, 'nombreComercial', Emisor::nombreComercial());
        self::el($w, 'ruc', Emisor::ruc());
        self::el($w, 'claveAcceso', $g['clave_acceso']);
        self::el($w, 'codDoc', '06');
        self::el($w, 'estab', Emisor::estab());
        self::el($w, 'ptoEmi', Emisor::ptoEmi());
        self::el($w, 'secuencial', str_pad((string) $g['secuencial'], 9, '0', STR_PAD_LEFT));
        self::el($w, 'dirMatriz', Emisor::dirMatriz());
        $w->endElement();

        // ===== infoGuiaRemision =====
        $w->startElement('infoGuiaRemision');
        self::el($w, 'dirEstablecimiento', Emisor::dirEstab() ?: Emisor::dirMatriz());
        self::el($w, 'dirPartida', $g['dir_partida'] ?: (Emisor::dirEstab() ?: Emisor::dirMatriz()));
        self::el($w, 'razonSocialTransportista', $g['transportista']['razon']);
        self::el($w, 'tipoIdentificacionTransportista', $g['transportista']['tipo_id'] ?? '04');
        self::el($w, 'rucTransportista', $g['transportista']['ruc']);
        if (Emisor::obligadoContabilidad() === 'SI') self::el($w, 'obligadoContabilidad', 'SI');
        self::el($w, 'fechaIniTransporte', $g['fecha_ini']);
        self::el($w, 'fechaFinTransporte', $g['fecha_fin']);
        if (! empty($g['transportista']['placa'])) self::el($w, 'placa', $g['transportista']['placa']);
        $w->endElement();

        // ===== destinatarios =====
        $w->startElement('destinatarios');
        $d = $g['destinatario'];
        $w->startElement('destinatario');
        self::el($w, 'identificacionDestinatario', $d['identificacion']);
        self::el($w, 'razonSocialDestinatario', $d['razon']);
        self::el($w, 'dirDestinatario', $d['direccion'] ?: 'S/N');
        self::el($w, 'motivoTraslado', $d['motivo'] ?: 'Entrega de mercadería');
        if (! empty($d['doc_sustento']['cod'])) {
            self::el($w, 'codDocSustento', $d['doc_sustento']['cod']);          // 01 factura
            self::el($w, 'numDocSustento', $d['doc_sustento']['num']);
            self::el($w, 'numAutDocSustento', $d['doc_sustento']['num_aut']);
            self::el($w, 'fechaEmisionDocSustento', $d['doc_sustento']['fecha']);
        }
        if (! empty($d['ruta'])) self::el($w, 'ruta', $d['ruta']);

        // items
        $w->startElement('detalles');
        foreach ($g['items'] as $it) {
            $w->startElement('detalle');
            self::el($w, 'codigoInterno', (string) ($it['codigo'] ?? 'ITEM'));
            self::el($w, 'descripcion', (string) $it['descripcion']);
            self::el($w, 'cantidad', number_format((float) $it['cantidad'], 2, '.', ''));
            $w->endElement();
        }
        $w->endElement(); // detalles

        $w->endElement(); // destinatario
        $w->endElement(); // destinatarios

        // ===== infoAdicional =====
        if (! empty($g['info_adicional'])) {
            $w->startElement('infoAdicional');
            foreach ($g['info_adicional'] as $nombre => $valor) {
                if ($valor === null || $valor === '') continue;
                $w->startElement('campoAdicional');
                $w->writeAttribute('nombre', mb_substr((string) $nombre, 0, 300));
                $w->text(mb_substr((string) $valor, 0, 300));
                $w->endElement();
            }
            $w->endElement();
        }

        $w->endElement(); // guiaRemision
        $w->endDocument();
        return $w->outputMemory();
    }

    protected static function el(XMLWriter $w, string $name, ?string $value): void
    {
        $w->startElement($name);
        $w->text((string) $value);
        $w->endElement();
    }
}
