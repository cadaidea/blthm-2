<?php

namespace App\Services\Sri;

/**
 * Genera el XML de NOTA DE CRÉDITO del SRI (codDoc 04, versión 1.1.0).
 * Estructura: infoTributaria + infoNotaCredito + detalles + infoAdicional.
 * Reversa una factura: mismos ítems y totales, con referencia al documento modificado.
 *
 * $data = [
 *   'clave_acceso','secuencial','fecha'(d/m/Y),
 *   'comprador'=>['tipo_id','identificacion','razon','direccion','email','telefono'],
 *   'items'=>[['codigo','descripcion','cantidad','precio_unitario','descuento','iva_rate']],
 *   'doc_modificado'=>['cod_doc'=>'01','num'=>'001-001-000000007','fecha_emision'=>'27/06/2026'],
 *   'motivo'=>'Anulación de factura',
 * ]
 */
class XmlNotaCredito
{
    public static function generar(array $data): string
    {
        $estab = Emisor::estab();
        $ptoEmi = Emisor::ptoEmi();
        $ambiente = Emisor::ambiente();

        $totalSinImp = 0.0;
        $impuestosAgrupados = [];
        $detalles = [];
        foreach ($data['items'] as $it) {
            $cant = (float) $it['cantidad'];
            $pu = round((float) $it['precio_unitario'], 6);
            $desc = round((float) ($it['descuento'] ?? 0), 2);
            $rate = (float) ($it['iva_rate'] ?? 15);
            $base = round($cant * $pu - $desc, 2);
            $codImp = '2';
            $codPorc = XmlFactura::codigoPorcentaje($rate);
            $valIva = round($base * $rate / 100, 2);
            $totalSinImp += $base;
            $key = $codPorc;
            if (! isset($impuestosAgrupados[$key])) $impuestosAgrupados[$key] = ['codigo' => $codImp, 'codigoPorcentaje' => $codPorc, 'baseImponible' => 0, 'valor' => 0, 'tarifa' => $rate];
            $impuestosAgrupados[$key]['baseImponible'] += $base;
            $impuestosAgrupados[$key]['valor'] += $valIva;
            $detalles[] = compact('it', 'cant', 'pu', 'desc', 'base', 'codImp', 'codPorc', 'rate', 'valIva');
        }
        $totalSinImp = round($totalSinImp, 2);
        $valorModificacion = round($totalSinImp + array_sum(array_column($impuestosAgrupados, 'valor')), 2);

        $w = new \XMLWriter();
        $w->openMemory();
        $w->startDocument('1.0', 'UTF-8');
        $w->startElement('notaCredito');
        $w->writeAttribute('id', 'comprobante');
        $w->writeAttribute('version', '1.1.0');

        // infoTributaria
        $w->startElement('infoTributaria');
        self::el($w, 'ambiente', $ambiente);
        self::el($w, 'tipoEmision', Emisor::tipoEmision());
        self::el($w, 'razonSocial', Emisor::razon());
        self::el($w, 'nombreComercial', Emisor::nombreComercial());
        self::el($w, 'ruc', Emisor::ruc());
        self::el($w, 'claveAcceso', $data['clave_acceso']);
        self::el($w, 'codDoc', '04');
        self::el($w, 'estab', $estab);
        self::el($w, 'ptoEmi', $ptoEmi);
        self::el($w, 'secuencial', str_pad($data['secuencial'], 9, '0', STR_PAD_LEFT));
        self::el($w, 'dirMatriz', Emisor::dirMatriz());
        if (Emisor::regimenMicroempresas()) self::el($w, 'contribuyenteRimpe', 'CONTRIBUYENTE RÉGIMEN RIMPE');
        $w->endElement();

        // infoNotaCredito
        $c = $data['comprador'];
        $dm = $data['doc_modificado'];
        $w->startElement('infoNotaCredito');
        self::el($w, 'fechaEmision', $data['fecha']);
        self::el($w, 'dirEstablecimiento', Emisor::dirEstab());
        self::el($w, 'tipoIdentificacionComprador', $c['tipo_id']);
        self::el($w, 'razonSocialComprador', $c['razon']);
        self::el($w, 'identificacionComprador', $c['identificacion']);
        if (Emisor::contribuyenteEspecial()) self::el($w, 'contribuyenteEspecial', Emisor::contribuyenteEspecial());
        self::el($w, 'obligadoContabilidad', Emisor::obligadoContabilidad());
        self::el($w, 'codDocModificado', $dm['cod_doc'] ?? '01');
        self::el($w, 'numDocModificado', $dm['num']);
        self::el($w, 'fechaEmisionDocSustento', $dm['fecha_emision']);
        self::el($w, 'totalSinImpuestos', number_format($totalSinImp, 2, '.', ''));
        self::el($w, 'valorModificacion', number_format($valorModificacion, 2, '.', ''));
        self::el($w, 'moneda', 'DOLAR');
        // totalConImpuestos
        $w->startElement('totalConImpuestos');
        foreach ($impuestosAgrupados as $imp) {
            $w->startElement('totalImpuesto');
            self::el($w, 'codigo', $imp['codigo']);
            self::el($w, 'codigoPorcentaje', $imp['codigoPorcentaje']);
            self::el($w, 'baseImponible', number_format(round($imp['baseImponible'], 2), 2, '.', ''));
            self::el($w, 'valor', number_format(round($imp['valor'], 2), 2, '.', ''));
            $w->endElement();
        }
        $w->endElement();
        self::el($w, 'motivo', $data['motivo'] ?? 'Anulación');
        $w->endElement(); // infoNotaCredito

        // detalles
        $w->startElement('detalles');
        foreach ($detalles as $d) {
            $it = $d['it'];
            $w->startElement('detalle');
            self::el($w, 'codigoInterno', (string) ($it['codigo'] ?? 'PROD'));
            self::el($w, 'descripcion', (string) $it['descripcion']);
            self::el($w, 'cantidad', number_format($d['cant'], 6, '.', ''));
            self::el($w, 'precioUnitario', number_format($d['pu'], 6, '.', ''));
            self::el($w, 'descuento', number_format($d['desc'], 2, '.', ''));
            self::el($w, 'precioTotalSinImpuesto', number_format($d['base'], 2, '.', ''));
            $w->startElement('impuestos');
            $w->startElement('impuesto');
            self::el($w, 'codigo', $d['codImp']);
            self::el($w, 'codigoPorcentaje', $d['codPorc']);
            self::el($w, 'tarifa', number_format($d['rate'], 2, '.', ''));
            self::el($w, 'baseImponible', number_format($d['base'], 2, '.', ''));
            self::el($w, 'valor', number_format($d['valIva'], 2, '.', ''));
            $w->endElement();
            $w->endElement();
            $w->endElement();
        }
        $w->endElement();

        // infoAdicional
        $w->startElement('infoAdicional');
        if (! empty($c['email'])) self::campoAdic($w, 'email', $c['email']);
        if (! empty($c['direccion'])) self::campoAdic($w, 'direccion', $c['direccion']);
        self::campoAdic($w, 'motivoNC', $data['motivo'] ?? 'Anulación');
        // Res. NAC-DGERCGC26-00000027: RUC del proveedor del sistema de facturacion (autoconsumo Bletia).
        self::campoAdic($w, 'proveedorFacturacion', Emisor::ruc());
        $w->endElement();

        $w->endElement(); // notaCredito
        $w->endDocument();
        return $w->outputMemory();
    }

    protected static function el(\XMLWriter $w, string $name, $value): void
    {
        $w->startElement($name); $w->text((string) $value); $w->endElement();
    }
    protected static function campoAdic(\XMLWriter $w, string $nombre, string $valor): void
    {
        $w->startElement('campoAdicional');
        $w->writeAttribute('nombre', $nombre);
        $w->text($valor);
        $w->endElement();
    }
}
