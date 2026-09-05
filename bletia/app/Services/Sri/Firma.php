<?php

namespace App\Services\Sri;

/** Orquestador de firma: usa el microservicio Node (probado con SRI) y cae a la nativa si falla. */
class Firma
{
    public static function firmar(string $xml, string $p12Path, string $p12Pass, string $tipo = 'factura'): array
    {
        $errores = [];
        // 1) microservicio Node (ec-sri-invoice-signer)
        $r = FirmaNode::firmar($xml, $p12Path, $p12Pass, $tipo);
        if ($r['ok']) return $r;
        $errores[] = 'node: ' . ($r['msg'] ?? '?');

        // 2) respaldo nativo (solo factura)
        if ($tipo === 'factura') {
            try {
                $firmado = FirmaXadesSri::firmar($xml, $p12Path, $p12Pass);
                if (strpos($firmado, 'SignatureValue') !== false) return ['ok' => true, 'xml' => $firmado, 'via' => 'nativa', 'aviso' => implode(' | ', $errores)];
            } catch (\Throwable $e) { $errores[] = 'nativa: ' . $e->getMessage(); }
        }
        return ['ok' => false, 'xml' => null, 'msg' => implode(' | ', $errores)];
    }
}
