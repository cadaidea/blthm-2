<?php

namespace App\Services\Sri;

use App\Models\Ajuste;

/** Datos del emisor y configuración SRI, leídos de Ajustes. */
class Emisor
{
    public static function get(string $k, $def = null)
    {
        return class_exists(Ajuste::class) ? (Ajuste::get($k) ?: $def) : $def;
    }

    public static function ruc(): string { return (string) self::get('emisor_ruc', ''); }
    public static function razon(): string { return (string) self::get('emisor_razon', ''); }
    public static function nombreComercial(): string { return (string) self::get('emisor_nombre_comercial', self::razon()); }
    public static function dirMatriz(): string { return (string) self::get('emisor_dir_matriz', ''); }
    public static function dirEstab(): string { return (string) self::get('emisor_dir_estab', self::dirMatriz()); }
    public static function obligadoContabilidad(): string { return self::get('emisor_obligado_contabilidad', 'NO') === 'SI' ? 'SI' : 'NO'; }
    public static function contribuyenteEspecial(): ?string { return self::get('emisor_contribuyente_especial'); }
    public static function ambiente(): string { return (string) self::get('sri_ambiente', '1') === '2' ? '2' : '1'; }
    public static function tipoEmision(): string { return '1'; } // 1 normal
    public static function estab(): string { return str_pad((string) self::get('emisor_estab', '001'), 3, '0', STR_PAD_LEFT); }
    public static function ptoEmi(): string { return str_pad((string) self::get('emisor_pto_emi', '001'), 3, '0', STR_PAD_LEFT); }
    public static function p12Path(): string { return (string) self::get('sri_p12_path', storage_path('app/sri/firma.p12')); }
    public static function p12Pass(): string { return (string) self::get('sri_p12_pass', ''); }
    public static function logoPath(): ?string { return self::get('emisor_logo_path'); }
    public static function regimenMicroempresas(): bool { return self::get('emisor_regimen_micro', 'NO') === 'SI'; }
    public static function agenteRetencion(): ?string { return self::get('emisor_agente_retencion'); } // nro resolución

    /** URLs de los web services según ambiente. */
    public static function wsRecepcion(): string
    {
        return self::ambiente() === '2'
            ? 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl'
            : 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl';
    }
    public static function wsAutorizacion(): string
    {
        return self::ambiente() === '2'
            ? 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl'
            : 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl';
    }

    public static function completo(): array
    {
        $faltan = [];
        foreach (['emisor_ruc' => self::ruc(), 'emisor_razon' => self::razon(), 'emisor_dir_matriz' => self::dirMatriz()] as $k => $v) {
            if (! $v) $faltan[] = $k;
        }
        if (! is_file(self::p12Path())) $faltan[] = 'archivo .p12 (' . self::p12Path() . ')';
        if (! self::p12Pass()) $faltan[] = 'sri_p12_pass';
        return ['ok' => empty($faltan), 'faltan' => $faltan];
    }
}
