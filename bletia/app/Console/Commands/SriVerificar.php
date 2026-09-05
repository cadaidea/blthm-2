<?php

namespace App\Console\Commands;

use App\Services\Sri\Emisor;
use App\Services\Sri\FirmaXadesSri;
use App\Services\Sri\XmlFactura;
use App\Services\Sri\ClaveAcceso;
use Illuminate\Console\Command;

class SriVerificar extends Command
{
    protected $signature = 'sri:verificar';
    protected $description = 'Verifica cada DigestValue localmente para saber cuál falla.';

    public function handle(): int
    {
        $fecha = date('d/m/Y');
        $clave = ClaveAcceso::generar($fecha, '01', Emisor::ruc(), Emisor::ambiente(), Emisor::estab(), Emisor::ptoEmi(), '000000999', '1');
        $xml = XmlFactura::generar([
            'clave_acceso' => $clave, 'secuencial' => '000000999', 'fecha' => $fecha,
            'comprador' => ['tipo_id' => '07', 'identificacion' => '9999999999999', 'razon' => 'CONSUMIDOR FINAL', 'direccion' => 'Cuenca'],
            'items' => [['codigo' => 'P1', 'descripcion' => 'Prueba', 'cantidad' => 1, 'precio_unitario' => 10.00, 'descuento' => 0, 'iva_rate' => 15]],
            'forma_pago' => '01',
        ]);
        $firmado = FirmaXadesSri::firmar($xml, Emisor::p12Path(), Emisor::p12Pass());
        file_put_contents(storage_path('app/sri/verif.xml'), $firmado);

        $doc = new \DOMDocument();
        $doc->preserveWhiteSpace = true;
        $doc->loadXML($firmado);
        $xp = new \DOMXPath($doc);
        $xp->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
        $xp->registerNamespace('etsi', 'http://uri.etsi.org/01903/v1.3.2#');

        // 1) Digest del comprobante (#comprobante): C14N del root SIN la firma (enveloped)
        $root = $doc->documentElement;
        $clon = $root->cloneNode(true);
        // remover Signature del clon
        $tmpDoc = new \DOMDocument();
        $tmpDoc->appendChild($tmpDoc->importNode($clon, true));
        $xp2 = new \DOMXPath($tmpDoc);
        $xp2->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
        foreach ($xp2->query('//ds:Signature') as $sig) $sig->parentNode->removeChild($sig);
        $calcComprobante = base64_encode(sha1($tmpDoc->documentElement->C14N(false, false), true));

        // 2) Digest de KeyInfo
        $ki = $xp->query('//ds:Signature/ds:KeyInfo')->item(0);
        $calcKeyInfo = $ki ? base64_encode(sha1($ki->C14N(true, false), true)) : 'N/A';

        // 3) Digest de SignedProperties
        $sp = $xp->query('//etsi:SignedProperties')->item(0);
        $calcSp = $sp ? base64_encode(sha1($sp->C14N(true, false), true)) : 'N/A';

        // leer los DigestValue que están en el XML
        $refs = $xp->query('//ds:SignedInfo/ds:Reference');
        $puestos = [];
        foreach ($refs as $r) {
            $uri = $r->getAttribute('URI');
            $type = $r->getAttribute('Type');
            $dv = $xp->query('./ds:DigestValue', $r)->item(0)->nodeValue;
            $tag = str_contains($type, 'SignedProperties') ? 'SignedProperties' : ($uri === '#comprobante' ? 'comprobante' : 'KeyInfo');
            $puestos[$tag] = $dv;
        }

        $this->line('');
        $this->line('=== COMPARACIÓN DE DIGESTS (puesto vs C14N-recalculado) ===');
        foreach (['comprobante' => $calcComprobante, 'KeyInfo' => $calcKeyInfo, 'SignedProperties' => $calcSp] as $k => $calc) {
            $puesto = $puestos[$k] ?? 'N/A';
            $ok = ($puesto === $calc) ? 'OK' : 'DIFERENTE';
            $this->line(sprintf('%-18s puesto=%s', $k, $puesto));
            $this->line(sprintf('%-18s c14n  =%s  => %s', '', $calc, $ok));
        }
        $this->line('');
        $this->line('Nota: el SRI usa "agregar namespaces" para KeyInfo/SignedProperties, no C14N puro.');
        $this->line('Si comprobante DIFERENTE -> el digest del documento es el problema.');
        return self::SUCCESS;
    }
}
