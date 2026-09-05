<?php

namespace App\Services\Sri;

/** Cliente del microservicio de firma Node (ec-sri-invoice-signer). */
class FirmaNode
{
    public static function url(): string
    {
        $u = class_exists(\App\Models\Ajuste::class) ? (\App\Models\Ajuste::get('sri_firma_url') ?: null) : null;
        return $u ?: 'http://127.0.0.1:3939/firmar';
    }

    /** Firma el XML llamando al microservicio. $tipo: factura|nota_credito|nota_debito|retencion|guia_remision */
    public static function firmar(string $xml, string $p12Path, string $p12Pass, string $tipo = 'factura'): array
    {
        $payload = json_encode(['xml' => $xml, 'p12Path' => $p12Path, 'password' => $p12Pass, 'tipo' => $tipo]);
        $ch = curl_init(self::url());
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);
        $resp = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($err) return ['ok' => false, 'msg' => 'No se pudo contactar el firmador: ' . $err];
        $data = json_decode($resp, true);
        if (! is_array($data)) return ['ok' => false, 'msg' => 'Respuesta inválida del firmador: ' . substr((string) $resp, 0, 200)];
        if (empty($data['ok'])) return ['ok' => false, 'msg' => $data['error'] ?? 'Error de firma'];
        return ['ok' => true, 'xml' => $data['signed'], 'via' => 'node'];
    }
}
