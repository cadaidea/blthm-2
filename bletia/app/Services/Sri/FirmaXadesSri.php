<?php

namespace App\Services\Sri;

/**
 * Firma XAdES-BES para el SRI Ecuador usando C14N real (vía DOM) para los 3 digests
 * y para SignedInfo. Construye la estructura XAdES con DOMDocument para que la
 * canonicalización coincida con la que recalcula el validador del SRI.
 */
class FirmaXadesSri
{
    const DS = 'http://www.w3.org/2000/09/xmldsig#';
    const ETSI = 'http://uri.etsi.org/01903/v1.3.2#';

    public static function firmar(string $xml, string $p12Path, string $p12Pass): string
    {
        if (! is_file($p12Path)) throw new \RuntimeException('No se encontró el .p12: ' . $p12Path);
        $certs = [];
        if (! openssl_pkcs12_read(file_get_contents($p12Path), $certs, $p12Pass)) {
            throw new \RuntimeException('No se pudo abrir el .p12: ' . openssl_error_string());
        }
        $pkey = $certs['pkey'];
        $cert = $certs['cert'];
        $info = openssl_x509_parse($cert);

        $certB64 = self::pemABase64($cert);
        $certDer = base64_decode($certB64);
        $x509Cert = chunk_split($certB64, 76, "\n"); $x509Cert = rtrim($x509Cert, "\n");
        $pub = openssl_pkey_get_details(openssl_pkey_get_public($cert));
        $modulus = rtrim(chunk_split(base64_encode($pub['rsa']['n'] ?? ''), 76, "\n"), "\n");
        $exponent = base64_encode($pub['rsa']['e'] ?? '');
        $certHash = base64_encode(sha1($certDer, true));
        $issuer = self::issuer($info['issuer'] ?? []);
        $serial = (string) ($info['serialNumber'] ?? '0');

        $n = random_int(100000, 999999);
        $signatureId = "Signature$n";
        $signedPropertiesId = "Signature$n-SignedProperties" . random_int(100000, 999999);
        $certificateId = "Certificate" . random_int(100000, 999999);
        $signedPropsRef = "SignedPropertiesID" . random_int(100000, 999999);
        $referenceIdDoc = "Reference-ID-" . random_int(100000, 999999);
        $signedInfoId = "Signature-SignedInfo" . random_int(100000, 999999);
        $signatureValueId = "SignatureValue" . random_int(100000, 999999);
        $objectId = "$signatureId-Object" . random_int(100000, 999999);
        $fecha = date('Y-m-d\TH:i:sP');

        // ===== documento del comprobante =====
        $doc = new \DOMDocument();
        $doc->preserveWhiteSpace = true;
        $doc->formatOutput = false;
        $doc->loadXML($xml);
        $root = $doc->documentElement;

        // digest del comprobante: C14N del root (enveloped, aún sin firma)
        $sha1Comprobante = base64_encode(sha1($root->C14N(false, false), true));

        // ===== construir la firma como DOM dentro del MISMO documento =====
        $signature = $doc->createElementNS(self::DS, 'ds:Signature');
        $signature->setAttribute('Id', $signatureId);
        $signature->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:etsi', self::ETSI);

        // --- SignedProperties (se construye primero para su digest) ---
        $object = $doc->createElementNS(self::DS, 'ds:Object');
        $object->setAttribute('Id', $objectId);
        $qp = $doc->createElementNS(self::ETSI, 'etsi:QualifyingProperties');
        $qp->setAttribute('Target', '#' . $signatureId);
        $signedProps = $doc->createElementNS(self::ETSI, 'etsi:SignedProperties');
        $signedProps->setAttribute('Id', $signedPropertiesId);
        $ssp = $doc->createElementNS(self::ETSI, 'etsi:SignedSignatureProperties');
        $ssp->appendChild($doc->createElementNS(self::ETSI, 'etsi:SigningTime', $fecha));
        $sc = $doc->createElementNS(self::ETSI, 'etsi:SigningCertificate');
        $certEl = $doc->createElementNS(self::ETSI, 'etsi:Cert');
        $cd = $doc->createElementNS(self::ETSI, 'etsi:CertDigest');
        $dmc = $doc->createElementNS(self::DS, 'ds:DigestMethod'); $dmc->setAttribute('Algorithm', self::DS . 'sha1');
        $cd->appendChild($dmc);
        $cd->appendChild($doc->createElementNS(self::DS, 'ds:DigestValue', $certHash));
        $certEl->appendChild($cd);
        $isr = $doc->createElementNS(self::ETSI, 'etsi:IssuerSerial');
        $isr->appendChild($doc->createElementNS(self::DS, 'ds:X509IssuerName', $issuer));
        $isr->appendChild($doc->createElementNS(self::DS, 'ds:X509SerialNumber', $serial));
        $certEl->appendChild($isr);
        $sc->appendChild($certEl);
        $ssp->appendChild($sc);
        $signedProps->appendChild($ssp);
        $sdop = $doc->createElementNS(self::ETSI, 'etsi:SignedDataObjectProperties');
        $dof = $doc->createElementNS(self::ETSI, 'etsi:DataObjectFormat'); $dof->setAttribute('ObjectReference', '#' . $referenceIdDoc);
        $dof->appendChild($doc->createElementNS(self::ETSI, 'etsi:Description', 'contenido comprobante'));
        $dof->appendChild($doc->createElementNS(self::ETSI, 'etsi:MimeType', 'text/xml'));
        $sdop->appendChild($dof);
        $signedProps->appendChild($sdop);
        $qp->appendChild($signedProps);
        $object->appendChild($qp);

        // --- KeyInfo ---
        $keyInfo = $doc->createElementNS(self::DS, 'ds:KeyInfo');
        $keyInfo->setAttribute('Id', $certificateId);
        $x509Data = $doc->createElementNS(self::DS, 'ds:X509Data');
        $x509Data->appendChild($doc->createElementNS(self::DS, 'ds:X509Certificate', $x509Cert));
        $keyInfo->appendChild($x509Data);
        $kv = $doc->createElementNS(self::DS, 'ds:KeyValue');
        $rsa = $doc->createElementNS(self::DS, 'ds:RSAKeyValue');
        $rsa->appendChild($doc->createElementNS(self::DS, 'ds:Modulus', $modulus));
        $rsa->appendChild($doc->createElementNS(self::DS, 'ds:Exponent', $exponent));
        $kv->appendChild($rsa);
        $keyInfo->appendChild($kv);

        // Para C14N correcto de fragmentos, deben estar dentro del árbol con sus namespaces visibles.
        // Insertamos temporalmente Signature en el root para que SignedProperties/KeyInfo hereden ns.
        $signature->appendChild($object);   // contiene SignedProperties
        $signature->appendChild($keyInfo);
        $root->appendChild($signature);

        // digests con C14N real (ahora heredan ds y etsi del contexto)
        $sha1SignedProperties = base64_encode(sha1($signedProps->C14N(false, false), true));
        $sha1KeyInfo = base64_encode(sha1($keyInfo->C14N(false, false), true));

        // --- SignedInfo ---
        $signedInfo = $doc->createElementNS(self::DS, 'ds:SignedInfo');
        $signedInfo->setAttribute('Id', $signedInfoId);
        $cm = $doc->createElementNS(self::DS, 'ds:CanonicalizationMethod'); $cm->setAttribute('Algorithm', 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315');
        $signedInfo->appendChild($cm);
        $sm = $doc->createElementNS(self::DS, 'ds:SignatureMethod'); $sm->setAttribute('Algorithm', self::DS . 'rsa-sha1');
        $signedInfo->appendChild($sm);
        // Ref SignedProperties
        $r1 = $doc->createElementNS(self::DS, 'ds:Reference');
        $r1->setAttribute('Id', $signedPropsRef);
        $r1->setAttribute('Type', 'http://uri.etsi.org/01903#SignedProperties');
        $r1->setAttribute('URI', '#' . $signedPropertiesId);
        $dm1 = $doc->createElementNS(self::DS, 'ds:DigestMethod'); $dm1->setAttribute('Algorithm', self::DS . 'sha1');
        $r1->appendChild($dm1);
        $r1->appendChild($doc->createElementNS(self::DS, 'ds:DigestValue', $sha1SignedProperties));
        $signedInfo->appendChild($r1);
        // Ref KeyInfo
        $r2 = $doc->createElementNS(self::DS, 'ds:Reference');
        $r2->setAttribute('URI', '#' . $certificateId);
        $dm2 = $doc->createElementNS(self::DS, 'ds:DigestMethod'); $dm2->setAttribute('Algorithm', self::DS . 'sha1');
        $r2->appendChild($dm2);
        $r2->appendChild($doc->createElementNS(self::DS, 'ds:DigestValue', $sha1KeyInfo));
        $signedInfo->appendChild($r2);
        // Ref comprobante
        $r3 = $doc->createElementNS(self::DS, 'ds:Reference');
        $r3->setAttribute('Id', $referenceIdDoc);
        $r3->setAttribute('URI', '#comprobante');
        $trs = $doc->createElementNS(self::DS, 'ds:Transforms');
        $tr = $doc->createElementNS(self::DS, 'ds:Transform'); $tr->setAttribute('Algorithm', self::DS . 'enveloped-signature');
        $trs->appendChild($tr);
        $r3->appendChild($trs);
        $dm3 = $doc->createElementNS(self::DS, 'ds:DigestMethod'); $dm3->setAttribute('Algorithm', self::DS . 'sha1');
        $r3->appendChild($dm3);
        $r3->appendChild($doc->createElementNS(self::DS, 'ds:DigestValue', $sha1Comprobante));
        $signedInfo->appendChild($r3);

        // insertar SignedInfo como primer hijo de Signature
        $signature->insertBefore($signedInfo, $signature->firstChild);

        // firmar SignedInfo con C14N real
        $firma = '';
        if (! openssl_sign($signedInfo->C14N(true, false), $firma, $pkey, OPENSSL_ALGO_SHA1)) {
            throw new \RuntimeException('openssl_sign falló: ' . openssl_error_string());
        }
        $signatureValue = rtrim(chunk_split(base64_encode($firma), 76, "\n"), "\n");

        // insertar SignatureValue entre SignedInfo y KeyInfo
        $svEl = $doc->createElementNS(self::DS, 'ds:SignatureValue', $signatureValue);
        $svEl->setAttribute('Id', $signatureValueId);
        $signature->insertBefore($svEl, $keyInfo);

        return $doc->saveXML();
    }

    protected static function pemABase64(string $pem): string
    {
        $pem = str_replace(['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----'], '', $pem);
        return trim(preg_replace('/\s+/', '', $pem));
    }
    protected static function issuer(array $issuer): string
    {
        $orden = ['CN', 'OU', 'O', 'L', 'C'];
        $p = [];
        foreach ($orden as $k) if (! empty($issuer[$k])) $p[] = $k . '=' . (is_array($issuer[$k]) ? implode(',', $issuer[$k]) : $issuer[$k]);
        foreach ($issuer as $k => $v) if (! in_array($k, $orden, true)) $p[] = $k . '=' . (is_array($v) ? implode(',', $v) : $v);
        return implode(',', $p);
    }
}
