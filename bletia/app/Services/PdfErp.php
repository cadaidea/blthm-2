<?php

namespace App\Services;

use App\Models\Documento;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class PdfErp
{
    public static function empresa(): array
    {
        $g = fn ($k, $d = null) => class_exists(\App\Models\Ajuste::class) ? (\App\Models\Ajuste::get($k) ?: $d) : $d;
        return [
            'nombre'    => $g('marca', config('tienda.marca', config('app.name'))),
            'ruc'       => $g('erp_ruc', ''),
            'direccion' => $g('erp_direccion', ''),
            'telefono'  => $g('erp_telefono', ''),
            'ciudad'    => $g('erp_ciudad', 'Cuenca'),
            'email'     => $g('erp_email', config('mail.from.address')),
        ];
    }

    public static function logoEmpresa(): ?string
    {
        $g = fn ($k) => class_exists(\App\Models\Ajuste::class) ? \App\Models\Ajuste::get($k) : null;
        $rel = $g('erp_logo_pdf') ?: $g('logo_pdf');
        if (! $rel) return null;
        if (is_array($rel)) $rel = collect($rel)->filter()->first();
        $cands = [
            storage_path('app/public/' . ltrim((string) $rel, '/')),
            public_path('storage/' . ltrim((string) $rel, '/')),
            public_path(ltrim((string) $rel, '/')),
        ];
        foreach ($cands as $p) {
            if (is_file($p)) {
                $mime = function_exists('mime_content_type') ? @mime_content_type($p) : 'image/png';
                return 'data:' . ($mime ?: 'image/png') . ';base64,' . base64_encode(file_get_contents($p));
            }
        }
        return null;
    }
    public static function img($valor): ?string
    {
        if (! $valor) return null;
        $path = $valor;
        if (preg_match('#^https?://#i', (string) $valor)) {
            $p = parse_url($valor, PHP_URL_PATH) ?: '';
            $path = public_path(ltrim($p, '/'));
        } elseif (! File::exists($path)) {
            $path = public_path(ltrim($valor, '/'));
        }
        if (! $path || ! File::exists($path)) return null;
        $mime = function_exists('mime_content_type') ? @mime_content_type($path) : 'image/jpeg';
        return 'data:' . ($mime ?: 'image/jpeg') . ';base64,' . base64_encode(File::get($path));
    }

    protected static function val($obj, array $keys, $def = null)
    {
        foreach ($keys as $k) {
            if (is_object($obj) && isset($obj->$k) && $obj->$k !== null && $obj->$k !== '') return $obj->$k;
        }
        return $def;
    }

    protected static function fotoProducto($prod): ?string
    {
        if (! $prod) return null;
        foreach (['imagen', 'foto', 'imagen_principal'] as $c) {
            if (isset($prod->$c) && $prod->$c) { if ($d = self::img($prod->$c)) return $d; }
        }
        if (Schema::hasTable('producto_imagenes')) {
            $pi = DB::table('producto_imagenes')->where('producto_id', $prod->id)->orderBy('id')->first();
            if ($pi) {
                foreach (['ruta', 'url', 'path', 'archivo'] as $c) {
                    if (isset($pi->$c) && $pi->$c) { if ($d = self::img($pi->$c)) return $d; }
                }
            }
        }
        return null;
    }

    public static function items($pedido): array
    {
        if (! Schema::hasTable('pedido_items')) return [];
        $rows = DB::table('pedido_items')->where('pedido_id', $pedido->id)->get();
        $out = [];
        foreach ($rows as $r) {
            $prod = isset($r->producto_id) ? DB::table('productos')->where('id', $r->producto_id)->first() : null;
            $cant = (float) self::val($r, ['cantidad', 'qty'], 1);
            $precio = (float) self::val($r, ['pvp', 'precio_unitario', 'precio'], 0);
            $out[] = [
                'proveedor_id' => self::val($r, ['proveedor_id'], 0),
                'nombre'   => self::val($r, ['nombre']) ?: self::val($prod, ['nombre'], 'Producto'),
                'sku'      => self::val($prod, ['sku'], ''),
                'cantidad' => $cant,
                'bultos'   => (int) self::val($r, ['bultos'], self::val($prod, ['bultos_default'], 1)),
                'precio'   => $precio,
                'subtotal' => (float) self::val($r, ['subtotal'], $precio * $cant),
                'variantes'        => self::val($r, ['variantes'], ''),
                'tapiz_principal'  => self::val($r, ['tapiz_principal'], ''),
                'tapiz_secundario' => self::val($r, ['tapiz_secundario'], ''),
                'cojines'          => self::val($r, ['cojines'], ''),
                'lacado'           => self::val($r, ['lacado'], ''),
                'notas'            => self::val($r, ['notas_adicionales', 'notas'], ''),
                'foto'             => self::fotoProducto($prod),
            ];
        }
        return $out;
    }

    protected static function cliente($pedido): array
    {
        $c = null;
        if (isset($pedido->cliente_id) && Schema::hasTable('clientes')) {
            $c = DB::table('clientes')->where('id', $pedido->cliente_id)->first();
        }
        return [
            'nombre'    => self::val($c, ['nombre', 'nombres'], 'Consumidor final'),
            'cedula'    => self::val($c, ['cedula_ruc', 'cedula', 'ruc'], ''),
            'email'     => self::val($c, ['email'], ''),
            'telefono'  => self::val($c, ['telefono'], ''),
            'celular'   => self::val($c, ['celular'], ''),
            'direccion' => self::val($c, ['direccion'], ''),
            'ciudad'    => self::val($c, ['ciudad'], ''),
            'provincia' => self::val($c, ['provincia'], ''),
        ];
    }

    protected static function ctx($pedido, ?array $itemsOverride = null): array
    {
        $items = $itemsOverride ?? self::items($pedido);
        return [
            'pedido'   => $pedido,
            'empresa'  => self::empresa(),
            'items'    => $items,
            'cliente'  => self::cliente($pedido),
            'numero'   => self::val($pedido, ['numero', 'nro', 'id'], $pedido->id ?? ''),
            'nro_contable' => self::val($pedido, ['nro_contable'], ''),
            'nro_factura'  => self::val($pedido, ['nro_factura'], ''),
            'total'    => (float) self::val($pedido, ['total'], array_sum(array_column($items, 'subtotal'))),
            'vendedor' => self::val($pedido, ['vendedor', 'vendedor_nombre'], ''),
            'fecha'    => now()->format('d/m/Y'),
            'total_bultos' => array_sum(array_column($items, 'bultos')),
        ];
    }

    protected static function destino(int $pedidoId, string $archivo): string
    {
        $dir = storage_path('app/public/erp/' . $pedidoId);
        File::ensureDirectoryExists($dir);
        return $dir . '/' . $archivo;
    }

    protected static function guardar($pedido, string $tipo, string $vista, array $extra = [], ?array $paper = null, ?array $itemsOverride = null): string
    {
        $pid = (int) ($pedido->id ?? 0);
        $archivo = $tipo . '-' . $pid . '.pdf';
        $abs = self::destino($pid, $archivo);
        $data = array_merge(self::ctx($pedido, $itemsOverride), $extra);

        $pdf = Pdf::loadView($vista, $data);
        $pdf->setPaper($paper ?: 'a4');
        $pdf->getDomPDF()->getOptions()->set('isRemoteEnabled', true);
        $pdf->save($abs);

        Documento::create([
            'pedido_id' => $pid, 'tipo' => $tipo, 'url' => url('storage/erp/' . $pid . '/' . $archivo),
            'ruta' => $abs, 'nombre_archivo' => $archivo, 'creado_en' => now(),
        ]);
        return $abs;
    }

    public static function resumenCompra($pedido): string { return self::guardar($pedido, 'resumen_cliente', 'erp.pdf.resumen'); }
    public static function ordenFabricacion($pedido): string { return self::guardar($pedido, 'orden_proveedor', 'erp.pdf.orden', ['conCliente' => false]); }
    public static function ordenCompleta($pedido): string { return self::guardar($pedido, 'orden_completa', 'erp.pdf.orden', ['conCliente' => true]); }
    public static function etiquetasBultos($pedido): string { return self::guardar($pedido, 'etiquetas', 'erp.pdf.etiquetas', [], [0, 0, 240.94, 155.91]); }
    public static function guiaRemision($pedido, $despacho = null): string { return self::guardar($pedido, 'guia_remision', 'erp.pdf.guia', ['despacho' => $despacho]); }
    public static function documentoEntrega($pedido): string { return self::guardar($pedido, 'documento_entrega', 'erp.pdf.entrega'); }

    /** Orden de fabricación filtrada por proveedor (sin datos de cliente). */
    public static function ordenProveedor($pedido, int $proveedorId): string
    {
        $items = array_values(array_filter(self::items($pedido), fn ($i) => (int) ($i['proveedor_id'] ?? 0) === $proveedorId));
        return self::guardar($pedido, 'orden_prov_' . $proveedorId, 'erp.pdf.orden', ['conCliente' => false], null, $items);
    }

    public static function todos($pedido): array
    {
        return [
            'resumen_cliente'   => self::resumenCompra($pedido),
            'orden_proveedor'   => self::ordenFabricacion($pedido),
            'orden_completa'    => self::ordenCompleta($pedido),
            'etiquetas'         => self::etiquetasBultos($pedido),
            'guia_remision'     => self::guiaRemision($pedido),
            'documento_entrega' => self::documentoEntrega($pedido),
        ];
    }

    /** Acta de entrega de material con firmas (en pantalla). Devuelve ruta absoluta del PDF. */
    public static function actaEntregaMaterial($mov, ?string $firmaRecibe = null): string
    {
        $mp = \App\Models\MateriaPrima::find($mov->materia_prima_id);
        $pedidoFolio = $mov->pedido_id ? (\Illuminate\Support\Facades\DB::table('pedidos')->where('id', $mov->pedido_id)->value('folio') ?: ('#' . $mov->pedido_id)) : '—';
        $pedRow = $mov->pedido_id ? \Illuminate\Support\Facades\DB::table('pedidos')->where('id', $mov->pedido_id)->first() : null;
        $clienteNom = ($pedRow && $pedRow->cliente_id) ? \Illuminate\Support\Facades\DB::table('clientes')->where('id', $pedRow->cliente_id)->value('nombre') : null;
        $productosTxt = $mov->pedido_id ? \Illuminate\Support\Facades\DB::table('pedido_items')->where('pedido_id', $mov->pedido_id)->pluck('nombre')->filter()->implode(', ') : null;
        $entrega = \Illuminate\Support\Facades\DB::table('users')->where('id', auth()->id())->first();

        $data = [
            'empresa'       => self::empresa(),
            'logo'          => self::logoEmpresa(),
            'folio'         => 'AEM-' . str_pad((string) $mov->id, 6, '0', STR_PAD_LEFT),
            'pedidoFolio'   => $pedidoFolio,
            'cliente'       => $clienteNom,
            'productos'     => $productosTxt,
            'fecha'         => now()->format('d/m/Y H:i'),
            'material'      => $mp->nombre ?? '—',
            'cantidad'      => number_format((float) $mov->cantidad, 2),
            'unidad'        => $mp->unidad ?? '',
            'nota'          => $mov->nota ?? null,
            'entregaNombre' => $entrega->name ?? '—',
            'entregaRol'    => $entrega->rol ?? '',
            'firmaEntrega'  => null,
            'recibeNombre'  => $mov->recibido_nombre ?? null,
            'recibeCedula'  => $mov->recibido_cedula ?? null,
            'firmaRecibe'   => $firmaRecibe ?: ($mov->firma ?? null),
        ];

        $dir = storage_path('app/public/actas');
        if (! is_dir($dir)) @mkdir($dir, 0775, true);
        $archivo = 'acta-material-' . $mov->id . '.pdf';
        $abs = $dir . '/' . $archivo;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('erp.pdf.acta-material', $data);
        $pdf->setPaper('a4');
        $pdf->getDomPDF()->getOptions()->set('isRemoteEnabled', true);
        $pdf->save($abs);

        \Illuminate\Support\Facades\DB::table('movimientos_material')->where('id', $mov->id)->update(['pdf_entrega' => 'actas/' . $archivo]);
        return $abs;
    }

    /** Acta de entrega del pedido al cliente con firma. Devuelve ruta absoluta del PDF. */
    public static function actaEntregaPedido($despacho, ?string $firmaCliente = null): string
    {
        $ped = \Illuminate\Support\Facades\DB::table('pedidos')->where('id', $despacho->pedido_id)->first();
        $pedidoFolio = $ped->folio ?? ('#' . $despacho->pedido_id);
        $cliente = ($ped && $ped->cliente_id) ? \Illuminate\Support\Facades\DB::table('clientes')->where('id', $ped->cliente_id)->value('nombre') : null;
        $retira = $ped->retira_local ?? false;
        $modalidad = $retira ? 'Retiro en local' : 'Entrega a domicilio';
        $lugar = $retira
            ? (\Illuminate\Support\Facades\DB::table('locales')->where('id', $despacho->local_retiro_id)->value('nombre') ?? '')
            : trim(($ped->direccion_envio ?? '') . ' · ' . ($ped->ciudad_envio ?? ''), ' ·');

        $items = [];
        foreach (\Illuminate\Support\Facades\DB::table('pedido_items')->where('pedido_id', $despacho->pedido_id)->get() as $it) {
            $det = trim(($it->variantes ?? '') . ' ' . ($it->tapiz_principal ? 'Tapiz: ' . $it->tapiz_principal : ''));
            $items[] = ['nombre' => $it->nombre, 'cantidad' => $it->cantidad, 'detalle' => $det];
        }

        $entregaDetalle = $despacho->conductor_nombre ?: ($retira ? 'Local' : '');

        $data = [
            'empresa'       => self::empresa(),
            'logo'          => self::logoEmpresa(),
            'pedidoFolio'   => $pedidoFolio,
            'cliente'       => $cliente,
            'fecha'         => now()->format('d/m/Y H:i'),
            'modalidad'     => $modalidad,
            'lugar'         => $lugar,
            'items'         => $items,
            'entregaNombre' => $despacho->conductor_nombre ?: (auth()->user()->name ?? '—'),
            'entregaDetalle'=> $entregaDetalle,
            'recibeNombre'  => $despacho->recibido_nombre ?? null,
            'recibeCedula'  => $despacho->recibido_cedula ?? null,
            'firmaCliente'  => $firmaCliente ?: ($despacho->firma_cliente ?? null),
        ];

        $dir = storage_path('app/public/actas');
        if (! is_dir($dir)) @mkdir($dir, 0775, true);
        $archivo = 'acta-entrega-' . $despacho->id . '.pdf';
        $abs = $dir . '/' . $archivo;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('erp.pdf.acta-entrega', $data);
        $pdf->setPaper('a4');
        $pdf->getDomPDF()->getOptions()->set('isRemoteEnabled', true);
        $pdf->save($abs);

        \Illuminate\Support\Facades\DB::table('despachos')->where('id', $despacho->id)->update(['pdf_entrega' => 'actas/' . $archivo]);
        return $abs;
    }

    public static function reclamoPdf($reclamo): string
    {
        $hist = \App\Services\HistorialPedido::de($reclamo->pedido_id);
        $cliente = $reclamo->cliente_id ? \Illuminate\Support\Facades\DB::table('clientes')->where('id', $reclamo->cliente_id)->first() : null;
        $fotos = [];
        foreach ((is_array($reclamo->fotos) ? $reclamo->fotos : []) as $f) {
            $abs = storage_path('app/public/' . ltrim($f, '/'));
            if (is_file($abs)) $fotos[] = $abs;
        }
        $data = [
            'empresa'  => self::empresa(),
            'logo'     => self::logoEmpresa(),
            'r'        => $reclamo,
            'hist'     => $hist,
            'cliente'  => $cliente,
            'fotos'    => $fotos,
            'fecha'    => now()->format('d/m/Y H:i'),
        ];
        $dir = storage_path('app/public/reclamos');
        if (! is_dir($dir)) @mkdir($dir, 0775, true);
        $abs = $dir . '/reclamo-' . ($reclamo->folio ?: $reclamo->id) . '.pdf';
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('erp.pdf.reclamo', $data)->setPaper('a4');
        $pdf->save($abs);
        return $abs;
    }

    /**
     * PDF de la orden de producción interna (abastecimiento propio).
     * Mismo estilo que ordenCompleta() de pedidos, con destino interno y los
     * materiales necesarios según la ficha (BOM) de cada producto.
     */
    public static function ordenCompra($compra): string
    {
        $items = [];
        foreach ($compra->items as $it) {
            $prod = $it->producto_id ? \Illuminate\Support\Facades\DB::table('productos')->where('id', $it->producto_id)->first() : null;
            $items[] = [
                'nombre' => $it->nombre,
                'cantidad' => (float) $it->cantidad,
                'bultos' => (int) ($it->bultos ?: ($prod->bultos_default ?? 1)),
                'foto' => self::fotoProducto($prod),
            ];
        }

        $materiales = [];
        foreach (\App\Services\Materiales::bomDeCompra($compra) as $mpId => $cant) {
            $mp = \App\Models\MateriaPrima::find($mpId);
            if ($mp) $materiales[] = ['nombre' => $mp->nombre, 'cantidad' => $cant, 'unidad' => $mp->unidad];
        }

        $data = [
            'empresa' => self::empresa(),
            'numero' => $compra->folio ?: ('#' . $compra->id),
            'fecha' => now()->format('d/m/Y H:i'),
            'destino' => optional($compra->localDestino)->nombre ?: '—',
            'items' => $items,
            'materiales' => $materiales,
        ];

        $dir = storage_path('app/public/erp/compras');
        if (! is_dir($dir)) @mkdir($dir, 0775, true);
        $abs = $dir . '/orden-' . $compra->id . '.pdf';
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('erp.pdf.orden-compra', $data);
        $pdf->setPaper('a4');
        $pdf->getDomPDF()->getOptions()->set('isRemoteEnabled', true);
        $pdf->save($abs);
        return $abs;
    }
}
