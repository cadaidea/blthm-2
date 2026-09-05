<?php

namespace App\Services\Sri;

/**
 * Firma XAdES-BES sobre comprobantes electrónicos del SRI (Ecuador).
 * Implementación nativa (sin librerías de pago) compatible con el esquema que valida el SRI.
 * Firma el documento completo (enveloped) más la referencia al certificado y propiedades firmadas.
 */
class FirmaXades
{
    /**
     * @param string $xml      XML del comprobante (sin firmar), UTF-8.
     * @param string $p12Path  Ruta absoluta al archivo .p12.
     * @param string $p12Pass  Clave del .p12.
     * @return string XML firmado.
     */
    public static function firmar(string $xml, string $p12Path, string $p12Pass): string
    {
        if (! is_file($p12Path)) throw new \RuntimeException('No se encontró el .p12 en: ' . $p12Path);
        $pkcs12 = file_get_contents($p12Path);
        $certs = [];
        if (! openssl_pkcs12_read($pkcs12, $certs, $p12Pass)) {
            throw new \RuntimeException('No se pudo abrir el .p12 (clave incorrecta o archivo dañado): ' . openssl_error_string());
        }
        $privateKey = $certs['pkey'];
        $cert = $certs['cert'];
        $extra = $certs['extracerts'] ?? [];

        // datos del certificado
        $certData = openssl_x509_parse($cert);
        $certClean = self::limpiarPem($cert);
        $certDer = base64_decode($certClean);
        $certDigest = base64_encode(hash('sha1', $certDer, true));
        $issuerName = self::issuerString($certData['issuer'] ?? []);
        $serial = $certData['serialNumber'] ?? '0';

        // X509 modulus/exponent para KeyInfo
        $pub = openssl_pkey_get_public($cert);
        $det = openssl_pkey_get_details($pub);
        $modulus = base64_encode($det['rsa']['n'] ?? '');
        $exponent = base64_encode($det['rsa']['e'] ?? '');

        // IDs únicos
        $rand = substr(str_replace(['.', ' '], '', uniqid('', true)), -6);
        $signatureId = 'Signature' . $rand;
        $signedPropsId = $signatureId . '-SignedProperties' . $rand;
        $signedInfoId = $signatureId . '-SignedInfo' . $rand;
        $keyInfoId = $signatureId . '-KeyInfo' . $rand;
        $refDocId = 'Reference-ID-' . $rand;
        $signedPropsRefId = 'SignedPropertiesID' . $rand;
        $objectId = $signatureId . '-Object' . $rand;

        // momento de la firma
        $signingTime = date('Y-m-d\TH:i:sP');

        // ----- SignedProperties -----
        $signedProperties =
            '<etsi:SignedProperties Id="' . $signedPropsId . '">'
            . '<etsi:SignedSignatureProperties>'
            . '<etsi:SigningTime>' . $signingTime . '</etsi:SigningTime>'
            . '<etsi:SigningCertificate>'
            . '<etsi:Cert>'
            . '<etsi:CertDigest>'
            . '<ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"></ds:DigestMethod>'
            . '<ds:DigestValue>' . $certDigest . '</ds:DigestValue>'
            . '</etsi:CertDigest>'
            . '<etsi:IssuerSerial>'
            . '<ds:X509IssuerName>' . $issuerName . '</ds:X509IssuerName>'
            . '<ds:X509SerialNumber>' . $serial . '</ds:X509SerialNumber>'
            . '</etsi:IssuerSerial>'
            . '</etsi:Cert>'
            . '</etsi:SigningCertificate>'
            . '</etsi:SignedSignatureProperties>'
            . '<etsi:SignedDataObjectProperties>'
            . '<etsi:DataObjectFormat ObjectReference="#' . $refDocId . '">'
            . '<etsi:Description>contenido comprobante</etsi:Description>'
            . '<etsi:MimeType>text/xml</etsi:MimeType>'
            . '</etsi:DataObjectFormat>'
            . '</etsi:SignedDataObjectProperties>'
            . '</etsi:SignedProperties>';

        // digest de SignedProperties (canonicalización simple: la cadena tal cual con namespaces heredados)
        $signedPropsForDigest = self::conNamespaces($signedProperties, [
            'xmlns:etsi="http://uri.etsi.org/01903/v1.3.2#"',
            'xmlns:ds="http://www.w3.org/2000/09/xmldsig#"',
        ], 'etsi:SignedProperties');
        $digestSignedProps = base64_encode(hash('sha1', $signedPropsForDigest, true));

        // digest del documento completo (enveloped)
        $digestDoc = base64_encode(hash('sha1', $xml, true));

        // KeyInfo
        $keyInfo =
            '<ds:KeyInfo Id="' . $keyInfoId . '">'
            . '<ds:X509Data>'
            . '<ds:X509Certificate>' . $certClean . '</ds:X509Certificate>'
            . '</ds:X509Data>'
            . '<ds:KeyValue>'
            . '<ds:RSAKeyValue>'
            . '<ds:Modulus>' . $modulus . '</ds:Modulus>'
            . '<ds:Exponent>' . $exponent . '</ds:Exponent>'
            . '</ds:RSAKeyValue>'
            . '</ds:KeyValue>'
            . '</ds:KeyInfo>';
        $keyInfoForDigest = self::conNamespaces($keyInfo, ['xmlns:ds="http://www.w3.org/2000/09/xmldsig#"'], 'ds:KeyInfo');
        $digestKeyInfo = base64_encode(hash('sha1', $keyInfoForDigest, true));

        // ----- SignedInfo -----
        $signedInfo =
            '<ds:SignedInfo Id="' . $signedInfoId . '">'
            . '<ds:CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"></ds:CanonicalizationMethod>'
            . '<ds:SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"></ds:SignatureMethod>'
            . '<ds:Reference Id="' . $signedPropsRefId . '" Type="http://uri.etsi.org/01903#SignedProperties" URI="#' . $signedPropsId . '">'
            . '<ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"></ds:DigestMethod>'
            . '<ds:DigestValue>' . $digestSignedProps . '</ds:DigestValue>'
            . '</ds:Reference>'
            . '<ds:Reference URI="#' . $keyInfoId . '">'
            . '<ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"></ds:DigestMethod>'
            . '<ds:DigestValue>' . $digestKeyInfo . '</ds:DigestValue>'
            . '</ds:Reference>'
            . '<ds:Reference Id="' . $refDocId . '" URI="#comprobante">'
            . '<ds:Transforms>'
            . '<ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"></ds:Transform>'
            . '</ds:Transforms>'
            . '<ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"></ds:DigestMethod>'
            . '<ds:DigestValue>' . $digestDoc . '</ds:DigestValue>'
            . '</ds:Reference>'
            . '</ds:SignedInfo>';

        // firmar SignedInfo
        $signedInfoForSign = self::conNamespaces($signedInfo, [
            'xmlns:ds="http://www.w3.org/2000/09/xmldsig#"',
        ], 'ds:SignedInfo');
        $signatureValue = '';
        openssl_sign($signedInfoForSign, $signatureValue, $privateKey, OPENSSL_ALGO_SHA1);
        $signatureValue = base64_encode($signatureValue);

        // ----- ensamblar Signature -----
        $signature =
            '<ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:etsi="http://uri.etsi.org/01903/v1.3.2#" Id="' . $signatureId . '">'
            . $signedInfo
            . '<ds:SignatureValue Id="' . $signatureId . '-SignatureValue">' . $signatureValue . '</ds:SignatureValue>'
            . $keyInfo
            . '<ds:Object Id="' . $objectId . '">'
            . '<etsi:QualifyingProperties Target="#' . $signatureId . '">'
            . $signedProperties
            . '</etsi:QualifyingProperties>'
            . '</ds:Object>'
            . '</ds:Signature>';

        // insertar antes de cerrar el root (enveloped)
        $xml = self::insertarFirma($xml, $signature);
        return $xml;
    }

    /** Quita cabeceras PEM y saltos. */
    protected static function limpiarPem(string $pem): string
    {
        $pem = preg_replace('/-----BEGIN CERTIFICATE-----/', '', $pem);
        $pem = preg_replace('/-----END CERTIFICATE-----/', '', $pem);
        return trim(preg_replace('/\s+/', '', $pem));
    }

    /** Construye el string del issuer en formato X500 (CN=..,OU=..,O=..,C=..). */
    protected static function issuerString(array $issuer): string
    {
        $orden = ['CN', 'OU', 'O', 'C', 'L', 'ST', 'emailAddress'];
        $partes = [];
        foreach ($orden as $k) {
            if (! empty($issuer[$k])) {
                $val = is_array($issuer[$k]) ? implode(',', $issuer[$k]) : $issuer[$k];
                $partes[] = $k . '=' . $val;
            }
        }
        foreach ($issuer as $k => $v) {
            if (in_array($k, $orden, true)) continue;
            $partes[] = $k . '=' . (is_array($v) ? implode(',', $v) : $v);
        }
        return implode(', ', $partes);
    }

    /** Inserta namespaces en el nodo raíz para canonicalizar un fragmento aislado. */
    protected static function conNamespaces(string $fragmento, array $ns, string $tag): string
    {
        $nsStr = ' ' . implode(' ', $ns);
        return preg_replace('/<' . preg_quote($tag, '/') . '/', '<' . $tag . $nsStr, $fragmento, 1);
    }

    /** Inserta la firma antes de la etiqueta de cierre del elemento raíz. */
    protected static function insertarFirma(string $xml, string $firma): string
    {
        // localizar el cierre del root: última etiqueta de cierre
        if (preg_match('/<\?xml.*?\?>/s', $xml, $m)) {
            $decl = $m[0];
            $cuerpo = substr($xml, strlen($decl));
        } else {
            $decl = '<?xml version="1.0" encoding="UTF-8"?>';
            $cuerpo = $xml;
        }
        $cuerpo = trim($cuerpo);
        // insertar antes del último </...>
        $pos = strrpos($cuerpo, '</');
        if ($pos === false) throw new \RuntimeException('XML sin etiqueta de cierre raíz.');
        $resultado = substr($cuerpo, 0, $pos) . $firma . substr($cuerpo, $pos);
        return $decl . "\n" . $resultado;
    }
}
