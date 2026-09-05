<?php

namespace App\Services\Sri;

/**
 * Genera el XML de FACTURA del SRI (codDoc 01, versión 2.1.0 sin contribuyente RIMPE,
 * o 1.1.0 según necesidad). Estructura: infoTributaria + infoFactura + detalles + infoAdicional.
 *
 * $data = [
 *   'clave_acceso', 'secuencial', 'fecha' (d/m/Y),
 *   'comprador' => ['tipo_id'(04/05/06/07),'identificacion','razon','direccion','email','telefono'],
 *   'items' => [ ['codigo','descripcion','cantidad','precio_unitario','descuento','iva_rate'] ],
 *   'propina' => 0,
 *   'forma_pago' => '20' (otros con utilización SF) | '19' tarjeta | '01' efectivo,
 * ]
 */
class XmlFactura
{
    public static function generar(array $data): string
    {
        $estab = Emisor::estab();
        $ptoEmi = Emisor::ptoEmi();
        $ambiente = Emisor::ambiente();

        // calcular totales
        $totalSinImp = 0.0; $totalDescuento = 0.0;
        $impuestosAgrupados = []; // por tarifa
        $detalles = [];
        foreach ($data['items'] as $it) {
            $cant = (float) $it['cantidad'];
            $pu = round((float) $it['precio_unitario'], 6); // unitario SIN IVA
            $desc = round((float) ($it['descuento'] ?? 0), 2);
            $rate = (float) ($it['iva_rate'] ?? 15);
            $base = round($cant * $pu - $desc, 2);
            $codImp = '2'; // IVA
            $codPorc = self::codigoPorcentaje($rate);
            $valIva = round($base * $rate / 100, 2);

            $totalSinImp += $base;
            $totalDescuento += $desc;
            $key = $codPorc;
            if (! isset($impuestosAgrupados[$key])) $impuestosAgrupados[$key] = ['codigo' => $codImp, 'codigoPorcentaje' => $codPorc, 'baseImponible' => 0, 'valor' => 0, 'tarifa' => $rate];
            $impuestosAgrupados[$key]['baseImponible'] += $base;
            $impuestosAgrupados[$key]['valor'] += $valIva;

            $detalles[] = compact('it', 'cant', 'pu', 'desc', 'base', 'codImp', 'codPorc', 'rate', 'valIva');
        }
        $totalSinImp = round($totalSinImp, 2);
        $totalDescuento = round($totalDescuento, 2);
        $propina = round((float) ($data['propina'] ?? 0), 2);
        $importeTotal = round($totalSinImp + array_sum(array_column($impuestosAgrupados, 'valor')) + $propina, 2);

        $w = new \XMLWriter();
        $w->openMemory();
        $w->startDocument('1.0', 'UTF-8');
        $w->startElement('factura');
        $w->writeAttribute('id', 'comprobante');
        $w->writeAttribute('version', '2.1.0');

        // infoTributaria
        $w->startElement('infoTributaria');
        self::el($w, 'ambiente', $ambiente);
        self::el($w, 'tipoEmision', Emisor::tipoEmision());
        self::el($w, 'razonSocial', Emisor::razon());
        self::el($w, 'nombreComercial', Emisor::nombreComercial());
        self::el($w, 'ruc', Emisor::ruc());
        self::el($w, 'claveAcceso', $data['clave_acceso']);
        self::el($w, 'codDoc', '01');
        self::el($w, 'estab', $estab);
        self::el($w, 'ptoEmi', $ptoEmi);
        self::el($w, 'secuencial', str_pad($data['secuencial'], 9, '0', STR_PAD_LEFT));
        self::el($w, 'dirMatriz', Emisor::dirMatriz());
        if (Emisor::agenteRetencion()) self::el($w, 'agenteRetencion', Emisor::agenteRetencion());
        if (Emisor::regimenMicroempresas()) self::el($w, 'contribuyenteRimpe', 'CONTRIBUYENTE RÉGIMEN RIMPE');
        $w->endElement();

        // infoFactura
        $c = $data['comprador'];
        $w->startElement('infoFactura');
        self::el($w, 'fechaEmision', $data['fecha']);
        self::el($w, 'dirEstablecimiento', Emisor::dirEstab());
        if (Emisor::contribuyenteEspecial()) self::el($w, 'contribuyenteEspecial', Emisor::contribuyenteEspecial());
        self::el($w, 'obligadoContabilidad', Emisor::obligadoContabilidad());
        self::el($w, 'tipoIdentificacionComprador', $c['tipo_id']);
        self::el($w, 'razonSocialComprador', $c['razon']);
        self::el($w, 'identificacionComprador', $c['identificacion']);
        if (! empty($c['direccion'])) self::el($w, 'direccionComprador', $c['direccion']);
        self::el($w, 'totalSinImpuestos', number_format($totalSinImp, 2, '.', ''));
        self::el($w, 'totalDescuento', number_format($totalDescuento, 2, '.', ''));
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
        self::el($w, 'propina', number_format($propina, 2, '.', ''));
        self::el($w, 'importeTotal', number_format($importeTotal, 2, '.', ''));
        self::el($w, 'moneda', 'DOLAR');
        // pagos (soporta múltiples formas de pago)
        $w->startElement('pagos');
        $pagos = $data['pagos'] ?? null;
        if (is_array($pagos) && count($pagos) > 0) {
            $sumaPagos = 0.0;
            foreach ($pagos as $pg) { $sumaPagos += (float) ($pg['monto'] ?? 0); }
            // ajustar por redondeo: si la suma difiere del total, corregir el último pago
            $dif = round($importeTotal - $sumaPagos, 2);
            $ultimo = count($pagos) - 1;
            foreach ($pagos as $i => $pg) {
                $monto = (float) ($pg['monto'] ?? 0);
                if ($i === $ultimo) $monto = round($monto + $dif, 2);
                $w->startElement('pago');
                self::el($w, 'formaPago', (string) ($pg['forma'] ?? '20'));
                self::el($w, 'total', number_format($monto, 2, '.', ''));
                if (! empty($pg['plazo'])) {
                    self::el($w, 'plazo', (string) (int) $pg['plazo']);
                    self::el($w, 'unidadTiempo', (string) ($pg['unidad'] ?? 'dias'));
                }
                $w->endElement();
            }
        } else {
            $w->startElement('pago');
            self::el($w, 'formaPago', $data['forma_pago'] ?? '20');
            self::el($w, 'total', number_format($importeTotal, 2, '.', ''));
            $w->endElement();
        }
        $w->endElement();
        $w->endElement(); // infoFactura

        // detalles
        $w->startElement('detalles');
        foreach ($detalles as $d) {
            $it = $d['it'];
            $w->startElement('detalle');
            self::el($w, 'codigoPrincipal', (string) ($it['codigo'] ?? 'PROD'));
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
            $w->endElement(); // detalle
        }
        $w->endElement(); // detalles

        // infoAdicional
        $w->startElement('infoAdicional');
        if (! empty($c['email'])) self::campoAdic($w, 'email', $c['email']);
        if (! empty($c['telefono'])) self::campoAdic($w, 'telefono', $c['telefono']);
        if (! empty($c['direccion'])) self::campoAdic($w, 'direccion', $c['direccion']);
        self::campoAdic($w, 'tipoVenta', $data['origen'] ?? 'Venta');
        // Res. NAC-DGERCGC26-00000027: RUC del proveedor del sistema de facturacion (autoconsumo Bletia).
        self::campoAdic($w, 'proveedorFacturacion', Emisor::ruc());
        $w->endElement();

        $w->endElement(); // factura
        $w->endDocument();
        return $w->outputMemory();
    }

    protected static function el(\XMLWriter $w, string $name, $value): void
    {
        $w->startElement($name);
        $w->text((string) $value);
        $w->endElement();
    }
    protected static function campoAdic(\XMLWriter $w, string $nombre, string $valor): void
    {
        $w->startElement('campoAdicional');
        $w->writeAttribute('nombre', $nombre);
        $w->text($valor);
        $w->endElement();
    }

    /** Código de porcentaje IVA del SRI según tarifa. */
    public static function codigoPorcentaje(float $rate): string
    {
        // 0 -> '0', 12 -> '2', 14 -> '3', 15 -> '4', no objeto -> '6', exento -> '7', diferenciado -> '8'
        return match (true) {
            $rate == 0.0 => '0',
            $rate == 12.0 => '2',
            $rate == 14.0 => '3',
            $rate == 15.0 => '4',
            default => '4',
        };
    }
}
