<?php

namespace App\Services\Sri;

class ClaveAcceso
{
    /**
     * Genera la clave de acceso de 49 dígitos del SRI.
     * Estructura: fecha(8) + tipoComp(2) + ruc(13) + ambiente(1) + serie(6=estab+ptoEmi) + secuencial(9) + codigoNumerico(8) + tipoEmision(1) + verificador(1)
     */
    public static function generar(
        string $fechaEmision,   // dd/mm/yyyy
        string $codDoc,         // 01,04,07,06
        string $ruc,            // 13
        string $ambiente,       // 1|2
        string $estab,          // 001
        string $ptoEmi,         // 001
        string $secuencial,     // 000000001 (9)
        string $tipoEmision = '1',
        ?string $codigoNumerico = null
    ): string {
        $fecha = self::fechaDdmmaaaa($fechaEmision);
        $serie = str_pad($estab, 3, '0', STR_PAD_LEFT) . str_pad($ptoEmi, 3, '0', STR_PAD_LEFT);
        $sec = str_pad($secuencial, 9, '0', STR_PAD_LEFT);
        $codNum = $codigoNumerico ?: str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        $base48 = $fecha . str_pad($codDoc, 2, '0', STR_PAD_LEFT) . str_pad($ruc, 13, '0', STR_PAD_LEFT)
            . $ambiente . $serie . $sec . $codNum . $tipoEmision;
        if (strlen($base48) !== 48) {
            throw new \RuntimeException('Clave de acceso base inválida (' . strlen($base48) . ' dígitos): ' . $base48);
        }
        return $base48 . self::digitoModulo11($base48);
    }

    /** Convierte dd/mm/yyyy (o Y-m-d) a ddmmyyyy. */
    protected static function fechaDdmmaaaa(string $f): string
    {
        $f = trim($f);
        if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $f, $m)) return $m[1] . $m[2] . $m[3];
        if (preg_match('#^(\d{4})-(\d{2})-(\d{2})#', $f, $m)) return $m[3] . $m[2] . $m[1];
        $ts = strtotime($f) ?: time();
        return date('dmY', $ts);
    }

    /** Dígito verificador módulo 11, pesos 2..7. Si da 11→0, 10→1. */
    public static function digitoModulo11(string $cadena): string
    {
        $peso = 2; $suma = 0;
        for ($i = strlen($cadena) - 1; $i >= 0; $i--) {
            $suma += ((int) $cadena[$i]) * $peso;
            $peso = ($peso === 7) ? 2 : $peso + 1;
        }
        $resto = $suma % 11;
        $dig = 11 - $resto;
        if ($dig === 11) $dig = 0;
        if ($dig === 10) $dig = 1;
        return (string) $dig;
    }
}
