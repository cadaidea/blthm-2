<?php
namespace App\Services\Sri;

/** Reconvierte un .p12 (cifrado legacy tipo BCE) a uno que PHP/OpenSSL 3.x puede abrir. */
class PrepararFirma
{
    /**
     * Reconvierte el .p12 en $origenPath y lo deja limpio en $salidaPath.
     * Devuelve ['ok'=>bool, 'msg'=>string].
     */
    public static function preparar(string $origenPath, string $password, string $salidaPath): array
    {
        if (! is_file($origenPath)) {
            return ['ok' => false, 'msg' => 'Archivo origen no encontrado.'];
        }

        $tmpPem = sys_get_temp_dir() . '/sri_' . uniqid() . '.pem';
        @mkdir(dirname($salidaPath), 0775, true);

        $passEsc = escapeshellarg($password);
        $origenEsc = escapeshellarg($origenPath);
        $tmpPemEsc = escapeshellarg($tmpPem);
        $salidaEsc = escapeshellarg($salidaPath);

        // 1) extraer todo (cert+key) del p12 legacy a PEM, probando con y sin -legacy
        $cmd1a = "openssl pkcs12 -legacy -in $origenEsc -out $tmpPemEsc -nodes -passin pass:$passEsc 2>&1";
        exec($cmd1a, $out1a, $code1a);

        if ($code1a !== 0 || ! is_file($tmpPem) || filesize($tmpPem) === 0) {
            // reintentar sin -legacy (por si el .p12 ya es formato moderno)
            $cmd1b = "openssl pkcs12 -in $origenEsc -out $tmpPemEsc -nodes -passin pass:$passEsc 2>&1";
            exec($cmd1b, $out1b, $code1b);
            if ($code1b !== 0 || ! is_file($tmpPem) || filesize($tmpPem) === 0) {
                @unlink($tmpPem);
                return ['ok' => false, 'msg' => 'OpenSSL no pudo leer el .p12: ' . implode(' ', array_merge($out1a, $out1b))];
            }
        }

        // 2) re-empaquetar en p12 con cifrado moderno (legible por openssl_pkcs12_read de PHP)
        $cmd2 = "openssl pkcs12 -export -in $tmpPemEsc -out $salidaEsc -passout pass:$passEsc -name firma 2>&1";
        exec($cmd2, $out2, $code2);
        @unlink($tmpPem);

        if ($code2 !== 0 || ! is_file($salidaPath) || filesize($salidaPath) === 0) {
            return ['ok' => false, 'msg' => 'No se pudo re-empaquetar el .p12: ' . implode(' ', $out2)];
        }

        // 3) verificar que PHP ya puede abrirlo
        $certs = [];
        if (! openssl_pkcs12_read((string) file_get_contents($salidaPath), $certs, $password)) {
            return ['ok' => false, 'msg' => 'Se generó el archivo pero PHP aún no puede abrirlo.'];
        }

        return ['ok' => true, 'msg' => 'Firma preparada (' . filesize($salidaPath) . ' bytes)'];
    }
}
