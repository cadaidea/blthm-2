<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Libro tributario del periodo: ventas emitidas y compras recibidas,
 * separadas por tipo de identificación (RUC / cédula / consumidor final),
 * con base imponible por tarifa de IVA, IVA y retenciones.
 * Solo LECTURA. Cruza tablas existentes (ventas, compras, clientes, proveedores, recibos).
 */
class LibroTributario
{
    /** Clasifica una identificación en ruc / cedula / consumidor_final. */
    protected static function clasificar(?string $tipo, ?string $ident): string
    {
        $tipo = strtolower((string) $tipo);
        if ($tipo === 'ruc') return 'ruc';
        if ($tipo === 'cedula') return 'cedula';
        if ($ident && strlen(preg_replace('/\D/', '', $ident)) === 13) return 'ruc';
        if ($ident && strlen(preg_replace('/\D/', '', $ident)) === 10) return 'cedula';
        return 'consumidor_final';
    }

    protected static function etiqueta(string $c): string
    {
        return match ($c) {
            'ruc' => 'RUC',
            'cedula' => 'Cédula',
            default => 'Consumidor final',
        };
    }

    /** @return array{ventas:array,compras:array,banca:array,resumen:array} */
    public static function periodo(string $desde, string $hasta): array
    {
        // ---------- VENTAS ----------
        $ventas = DB::table('ventas as v')
            ->leftJoin('clientes as c', 'c.id', '=', 'v.cliente_id')
            ->whereBetween('v.fecha', [$desde, $hasta])
            ->whereNotIn('v.estado', ['anulada', 'anulado'])
            ->select('v.fecha', 'v.numero_comprobante', 'v.tipo_comprobante', 'v.subtotal', 'v.iva', 'v.total',
                'v.ret_iva', 'v.ret_renta', 'v.ret_comprobante',
                'c.nombre as cliente', 'c.identificacion', 'c.tipo_identificacion')
            ->orderBy('v.fecha')->get();

        $ventasRows = [];
        $resVentas = ['ruc' => ['base' => 0, 'iva' => 0], 'cedula' => ['base' => 0, 'iva' => 0], 'consumidor_final' => ['base' => 0, 'iva' => 0]];
        foreach ($ventas as $v) {
            $clase = self::clasificar($v->tipo_identificacion, $v->identificacion);
            $resVentas[$clase]['base'] += (float) $v->subtotal;
            $resVentas[$clase]['iva']  += (float) $v->iva;
            $ventasRows[] = [
                $v->fecha,
                self::etiqueta($clase),
                $v->identificacion ?: '9999999999999',
                $v->cliente ?: 'CONSUMIDOR FINAL',
                strtoupper($v->tipo_comprobante ?: ''),
                $v->numero_comprobante ?: '',
                number_format((float) $v->subtotal, 2, '.', ''),
                number_format((float) $v->iva, 2, '.', ''),
                number_format((float) $v->total, 2, '.', ''),
                number_format((float) ($v->ret_iva ?? 0), 2, '.', ''),
                number_format((float) ($v->ret_renta ?? 0), 2, '.', ''),
                $v->ret_comprobante ?: '',
            ];
        }

        // ---------- COMPRAS ----------
        $compras = DB::table('compras as co')
            ->leftJoin('proveedores as p', 'p.id', '=', 'co.proveedor_id')
            ->whereBetween('co.doc_fecha', [$desde, $hasta])
            ->whereNotIn('co.estado', ['anulada', 'anulado'])
            ->select('co.doc_fecha', 'co.doc_tipo', 'co.doc_numero', 'co.subtotal', 'co.iva', 'co.total',
                'co.sustento_tributario', 'co.autorizacion_sri', 'co.ret_iva', 'co.ret_renta', 'co.ret_comprobante',
                'p.nombre as proveedor', 'p.identificacion', 'p.tipo_identificacion')
            ->orderBy('co.doc_fecha')->get();

        $comprasRows = [];
        $resCompras = ['ruc' => ['base' => 0, 'iva' => 0], 'cedula' => ['base' => 0, 'iva' => 0], 'consumidor_final' => ['base' => 0, 'iva' => 0]];
        foreach ($compras as $co) {
            $clase = self::clasificar($co->tipo_identificacion, $co->identificacion);
            $resCompras[$clase]['base'] += (float) $co->subtotal;
            $resCompras[$clase]['iva']  += (float) $co->iva;
            $comprasRows[] = [
                $co->doc_fecha,
                self::etiqueta($clase),
                $co->identificacion ?: '',
                $co->proveedor ?: '',
                strtoupper($co->doc_tipo ?: ''),
                $co->doc_numero ?: '',
                $co->sustento_tributario ?: '',
                $co->autorizacion_sri ?: '',
                number_format((float) $co->subtotal, 2, '.', ''),
                number_format((float) $co->iva, 2, '.', ''),
                number_format((float) $co->total, 2, '.', ''),
                number_format((float) ($co->ret_iva ?? 0), 2, '.', ''),
                number_format((float) ($co->ret_renta ?? 0), 2, '.', ''),
                $co->ret_comprobante ?: '',
            ];
        }

        // ---------- BANCARIZACIÓN (pagos no-efectivo sobre umbral) ----------
        $umbral = 1000.0;
        $bancaRows = [];
        if (Schema::hasTable('recibos')) {
            $recibos = DB::table('recibos as r')
                ->leftJoin('clientes as c', 'c.id', '=', 'r.cliente_id')
                ->whereBetween('r.fecha', [$desde, $hasta])
                ->where('r.monto', '>=', $umbral)
                ->whereRaw("LOWER(COALESCE(r.metodo,'')) NOT IN ('efectivo','cash')")
                ->select('r.fecha', 'r.folio', 'r.monto', 'r.metodo', 'r.nro_comprobante', 'r.cheque_banco',
                    'c.nombre as cliente', 'c.identificacion')
                ->orderBy('r.fecha')->get();
            foreach ($recibos as $r) {
                $bancaRows[] = [
                    $r->fecha,
                    $r->folio ?: '',
                    $r->cliente ?: '',
                    $r->identificacion ?: '',
                    ucfirst($r->metodo ?: ''),
                    $r->cheque_banco ?: '',
                    $r->nro_comprobante ?: '',
                    number_format((float) $r->monto, 2, '.', ''),
                ];
            }
        }

        // ---------- RESUMEN IVA ----------
        $totVentaBase = array_sum(array_column($resVentas, 'base'));
        $totVentaIva  = array_sum(array_column($resVentas, 'iva'));
        $totCompraBase = array_sum(array_column($resCompras, 'base'));
        $totCompraIva  = array_sum(array_column($resCompras, 'iva'));

        $resumen = [
            'desde' => $desde, 'hasta' => $hasta,
            'ventas' => $resVentas, 'compras' => $resCompras,
            'iva_cobrado' => $totVentaIva,
            'iva_pagado'  => $totCompraIva,
            'iva_a_pagar' => round($totVentaIva - $totCompraIva, 2),
            'total_ventas' => $totVentaBase, 'total_compras' => $totCompraBase,
            'n_ventas' => count($ventasRows), 'n_compras' => count($comprasRows),
        ];

        return [
            'ventas'  => $ventasRows,
            'compras' => $comprasRows,
            'banca'   => $bancaRows,
            'resumen' => $resumen,
        ];
    }

    /** Genera el Excel multi-hoja reusando ExportadorExcel. Devuelve el path. */
    public static function excel(string $desde, string $hasta): string
    {
        $d = self::periodo($desde, $hasta);

        $hojas = [
            'Ventas' => [
                'headers' => ['Fecha', 'Tipo ID', 'Identificación', 'Cliente', 'Comprobante', 'Número', 'Base', 'IVA', 'Total', 'Ret IVA', 'Ret Renta', 'N° Retención'],
                'rows' => $d['ventas'],
            ],
            'Compras' => [
                'headers' => ['Fecha', 'Tipo ID', 'Identificación', 'Proveedor', 'Documento', 'Número', 'Sustento', 'Autorización', 'Base', 'IVA', 'Total', 'Ret IVA', 'Ret Renta', 'N° Retención'],
                'rows' => $d['compras'],
            ],
            'Bancarizacion' => [
                'headers' => ['Fecha', 'Folio', 'Cliente', 'Identificación', 'Método', 'Banco', 'N° Comprobante', 'Monto'],
                'rows' => $d['banca'],
            ],
            'Resumen IVA' => [
                'headers' => ['Concepto', 'Valor'],
                'rows' => [
                    ['Periodo', $desde . ' a ' . $hasta],
                    ['Base ventas', number_format($d['resumen']['total_ventas'], 2, '.', '')],
                    ['IVA cobrado (ventas)', number_format($d['resumen']['iva_cobrado'], 2, '.', '')],
                    ['Base compras', number_format($d['resumen']['total_compras'], 2, '.', '')],
                    ['IVA pagado (compras)', number_format($d['resumen']['iva_pagado'], 2, '.', '')],
                    ['IVA a pagar (cobrado - pagado)', number_format($d['resumen']['iva_a_pagar'], 2, '.', '')],
                    ['Ventas a RUC (base)', number_format($d['resumen']['ventas']['ruc']['base'], 2, '.', '')],
                    ['Ventas a cédula (base)', number_format($d['resumen']['ventas']['cedula']['base'], 2, '.', '')],
                    ['Ventas consumidor final (base)', number_format($d['resumen']['ventas']['consumidor_final']['base'], 2, '.', '')],
                ],
            ],
        ];

        $nombre = 'libro-tributario-' . $desde . '-a-' . $hasta . '-' . now()->format('His') . '.xlsx';
        return ExportadorExcel::generar($hojas, $nombre);
    }
}
