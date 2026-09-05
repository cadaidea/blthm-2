<?php

namespace App\Services;

use App\Mail\DocumentoPedido;
use App\Models\LinkUnico;
use App\Support\CorreoBrand;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class EstadoPedidoErp
{
    public const ESTADOS = [
        'borrador'          => 'Borrador',
        'pendiente'         => 'Pendiente',
        'por_aprobar'       => 'Por aprobar',
        'aprobado'          => 'Aprobado',
        'confirmado'        => 'Confirmado',
        'enviado_proveedor' => 'Enviado a proveedor',
        'en_fabricacion'    => 'En fabricación',
        'en_produccion'     => 'En producción (taller)',
        'listo_proveedor'   => 'Listo en proveedor',
        'en_bodega'         => 'En bodega',
        'listo_despacho'    => 'Listo para despacho',
        'despachado'        => 'Despachado',
        'entregado'         => 'Entregado',
        'anulado'           => 'Anulado',
        'cancelado'         => 'Cancelado',
    ];

    public const ESTADOS_CLIENTE = [
        'borrador'          => 'En proceso',
        'pendiente'         => 'En revisión',
        'por_aprobar'       => 'En revisión',
        'aprobado'          => 'Confirmado',
        'confirmado'        => 'Confirmado',
        'enviado_proveedor' => 'En fabricación',
        'en_fabricacion'    => 'En fabricación',
        'en_produccion'     => 'En producción (taller)',
        'listo_proveedor'   => 'Casi listo',
        'en_bodega'         => 'En bodega',
        'listo_despacho'    => 'Listo para despacho',
        'despachado'        => 'Despachado',
        'entregado'         => 'Entregado',
        'anulado'           => 'Anulado',
        'cancelado'         => 'Anulado',
    ];

    /** Número visible del pedido: folio si existe, si no numero/id. */
    public static function numero($pedido)
    {
        return $pedido->folio ?? $pedido->numero ?? $pedido->id;
    }

    protected static function resumenCliente($pedido): string
    {
        if (! Schema::hasTable('pedido_items')) return '';
        $rows = DB::table('pedido_items')->where('pedido_id', $pedido->id)->get();
        if ($rows->isEmpty()) return '';
        $campos = [
            'tapiz_principal' => 'Tapiz principal', 'tapiz_secundario' => 'Tapiz secundario',
            'cojines' => 'Cojines principal', 'cojines_secundario' => 'Cojines secundario', 'lacado' => 'Lacado',
        ];
        $out = '';
        foreach ($rows as $r) {
            $modelo = $r->nombre ?: 'Modelo';
            $cant = (int) ($r->cantidad ?? 1);
            $li = '';
            foreach ($campos as $k => $lbl) {
                if (! empty($r->$k)) $li .= '<li>' . e($lbl) . ': ' . e($r->$k) . '</li>';
            }
            $out .= '<p style="margin:12px 0 4px"><strong>' . e($modelo) . '</strong>' . ($cant > 1 ? ' ×' . $cant : '') . '</p>';
            if ($li) $out .= '<ul style="margin:0 0 8px;padding-left:18px">' . $li . '</ul>';
        }
        return $out ? '<p>Detalle de tu pedido:</p>' . $out : '';
    }

    protected static function resumenProveedor($pedido, int $provId): string
    {
        if (! Schema::hasTable('pedido_items')) return '';
        $rows = DB::table('pedido_items')->where('pedido_id', $pedido->id)->where('proveedor_id', $provId)->get();
        if ($rows->isEmpty()) return '';
        $url = function ($p) { return $p ? Storage::disk('public')->url($p) : null; };
        $campos = [
            ['Tapiz principal', 'tapiz_principal', 'foto_tapiz_principal'],
            ['Tapiz secundario', 'tapiz_secundario', 'foto_tapiz_secundario'],
            ['Cojines principal', 'cojines', 'foto_cojines'],
            ['Cojines secundario', 'cojines_secundario', 'foto_cojines_secundario'],
            ['Lacado', 'lacado', 'foto_lacado'],
        ];
        $out = '';
        foreach ($rows as $r) {
            $out .= '<p style="margin:14px 0 4px"><strong>' . e($r->nombre ?: 'Modelo') . '</strong> · Cant: ' . (int) ($r->cantidad ?? 1) . '</p>';
            if (! empty($r->foto_modelo) && ($u = $url($r->foto_modelo))) $out .= '<p style="margin:2px 0"><a href="' . e($u) . '">Foto del modelo</a></p>';
            $li = '';
            foreach ($campos as $c) {
                if (! empty($r->{$c[1]})) {
                    $foto = ! empty($r->{$c[2]}) && ($u = $url($r->{$c[2]})) ? ' — <a href="' . e($u) . '">foto</a>' : '';
                    $li .= '<li>' . e($c[0]) . ': ' . e($r->{$c[1]}) . $foto . '</li>';
                }
            }
            if ($li) $out .= '<ul style="margin:0 0 6px;padding-left:18px">' . $li . '</ul>';
            if (! empty($r->notas_adicionales)) $out .= '<p style="margin:2px 0;color:#555"><em>Notas: ' . e($r->notas_adicionales) . '</em></p>';
        }
        return $out;
    }

    public static function mensajeCliente(string $estado, $pedido): ?array
    {
        $n = self::numero($pedido);
        return match ($estado) {
            'confirmado'        => ['Pedido ' . $n . ' confirmado', '<p>¡Gracias! Confirmamos tu pedido especial <strong>' . $n . '</strong> y comenzaremos a gestionarlo.</p>'],
            'enviado_proveedor' => ['Pedido ' . $n . ' en fabricación', '<p>Tu pedido <strong>' . $n . '</strong> entró en fabricación. Te avisaremos en cada avance.</p>' . self::resumenCliente($pedido)],
            'en_fabricacion'    => ['Pedido ' . $n . ' en fabricación', '<p>Tu pedido <strong>' . $n . '</strong> está en fabricación.</p>'],
            'listo_proveedor'   => ['Pedido ' . $n . ' casi listo', '<p>Tu pedido <strong>' . $n . '</strong> ya está terminado y pronto llegará a nuestra bodega.</p>'],
            'en_bodega'         => ['Pedido ' . $n . ' en bodega', '<p>Tu pedido <strong>' . $n . '</strong> llegó a nuestra bodega.</p>'],
            'listo_despacho'    => ['Pedido ' . $n . ' listo', '<p>Tu pedido <strong>' . $n . '</strong> está listo para despacho/retiro.</p>'],
            'despachado'        => ['Pedido ' . $n . ' despachado', '<p>Tu pedido <strong>' . $n . '</strong> fue despachado.</p>'],
            'entregado'         => ['Pedido ' . $n . ' entregado', '<p>Tu pedido <strong>' . $n . '</strong> fue entregado. ¡Gracias por tu compra!</p>'],
            'anulado'           => ['Pedido ' . $n . ' anulado', '<p>Tu pedido <strong>' . $n . '</strong> fue anulado.' . (!empty($pedido->observacion_anulacion) ? ' Motivo: ' . e($pedido->observacion_anulacion) : '') . '</p>'],
            'cancelado'         => ['Pedido ' . $n . ' anulado', '<p>Tu pedido <strong>' . $n . '</strong> fue anulado.' . (!empty($pedido->observacion_anulacion) ? ' Motivo: ' . e($pedido->observacion_anulacion) : '') . '</p>'],
            default             => null,
        };
    }

    public static function urlSeguimiento($pedido): string
    {
        try { return route('erp.seguimiento', ['p' => $pedido->id]); }
        catch (\Throwable $e) { return rtrim(config('app.url'), '/') . '/seguimiento?p=' . $pedido->id; }
    }

    public static function emailCliente($pedido): ?string
    {
        if (! isset($pedido->cliente_id) || ! Schema::hasTable('clientes')) return null;
        $c = DB::table('clientes')->where('id', $pedido->cliente_id)->first();
        return $c->email ?? null;
    }

    /** Correo del dueño (copia interna). */
    public static function dueno(): ?string
    {
        $v = class_exists(\App\Models\Ajuste::class) ? (\App\Models\Ajuste::get('erp_email_dueno') ?: null) : null;
        return $v ?: config('mail.from.address');
    }

    public static function avanzar($pedido, string $nuevo, bool $notificar = true): void
    {
        $de = $pedido->estado_erp ?? null;
        if (Schema::hasColumn('pedidos', 'estado_erp')) {
            DB::table('pedidos')->where('id', $pedido->id)->update(['estado_erp' => $nuevo]);
        }
        // crear despacho automáticamente al quedar listo para despacho
        if ($nuevo === 'listo_despacho') {
            self::crearDespachoSiCorresponde($pedido);
            try { \App\Services\Etiquetas::generar($pedido); } catch (\Throwable $e) { report($e); }
        }
        if (Schema::hasTable('historial_pedido')) {
            DB::table('historial_pedido')->insert([
                'pedido_id' => $pedido->id, 'estado_anterior' => $de, 'estado_nuevo' => $nuevo,
                'usuario_id' => optional(auth()->user())->id, 'notas' => null, 'creado_en' => now(),
            ]);
        }
        if ($notificar && ($msg = self::mensajeCliente($nuevo, $pedido))) {
            $html = CorreoBrand::wrap($msg[0], $msg[1], [
                'preheader' => trim(strip_tags($msg[1])),
                'cta' => ['text' => 'Ver pedido', 'url' => self::urlSeguimiento($pedido)],
            ]);
            if ($to = self::emailCliente($pedido)) {
                try { Mail::to($to)->send(new DocumentoPedido($msg[0], $html, [])); }
                catch (\Throwable $e) { report($e); }
            }
            if ($dueno = self::dueno()) {
                try { Mail::to($dueno)->send(new DocumentoPedido('[Interno] ' . $msg[0], $html, [])); }
                catch (\Throwable $e) { report($e); }
            }
        }
    }

    public static function anular($pedido, ?string $motivo = null, bool $notificar = true): void
    {
        $upd = [];
        if (Schema::hasColumn('pedidos', 'observacion_anulacion')) $upd['observacion_anulacion'] = $motivo;
        if (Schema::hasColumn('pedidos', 'anulado_at')) $upd['anulado_at'] = now();
        if (Schema::hasColumn('pedidos', 'folio_anulacion')) $upd['folio_anulacion'] = Folios::next('ANL');
        if ($upd) DB::table('pedidos')->where('id', $pedido->id)->update($upd);
        $pedido->observacion_anulacion = $motivo;
        self::avanzar($pedido, 'anulado', $notificar);
    }

    public static function enviarAProveedor($pedido): array
    {
        if (! Schema::hasTable('pedido_items')) return ['ok' => false, 'msg' => 'Sin ítems'];
        $rows = DB::table('pedido_items')->where('pedido_id', $pedido->id)->get();
        $porProv = [];
        foreach ($rows as $r) { $porProv[$r->proveedor_id ?? 0][] = $r; }

        $num = self::numero($pedido);
        $avisados = [];
        $ofs = [];
        $sinProv = false;
        foreach ($porProv as $provId => $items) {
            if (! $provId) { $sinProv = true; continue; }
            $prov = DB::table('proveedores')->where('id', $provId)->first();
            $of = Folios::next('OF');
            $ofs[] = $of;
            $pdf = PdfErp::ordenProveedor($pedido, (int) $provId);
            $etq = PdfErp::etiquetasBultos($pedido);
            $link = LinksErp::crear('proveedor', $pedido->id, null);
            $url = LinksErp::url($link);
            if ($prov && ! empty($prov->email)) {
                $cuerpo = '<p>Orden de fabricación <strong>' . $of . '</strong> · pedido <strong>' . $num . '</strong>.</p>'
                    . '<p style="margin:6px 0;color:#101015"><strong>Enviado a fabricar por:</strong> ' . \App\Services\Traza::textoQuien($pedido, 'enviado_fabricacion') . '</p>'
                    . self::resumenProveedor($pedido, (int) $provId)
                    . '<p style="margin-top:14px">Cuando esté listo, confirma y sube 2 fotos desde el botón.</p>';
                try {
                    Mail::to($prov->email)->send(new DocumentoPedido(
                        'Orden de fabricación ' . $of . ' · pedido ' . $num,
                        CorreoBrand::wrap('Orden de fabricación ' . $of, $cuerpo, [
                            'preheader' => 'Nueva orden de fabricación',
                            'cta' => ['text' => 'Confirmar y subir fotos', 'url' => $url],
                        ]),
                        [$pdf, $etq]
                    ));
                    $avisados[] = $prov->nombre;
                } catch (\Throwable $e) { report($e); }
            }
        }
        if ($ofs && Schema::hasColumn('pedidos', 'folio_of')) {
            DB::table('pedidos')->where('id', $pedido->id)->update(['folio_of' => implode(', ', $ofs)]);
        }
        self::avanzar($pedido, 'enviado_proveedor');
        return ['ok' => true, 'proveedores' => $avisados, 'sin_proveedor' => $sinProv];
    }

    /** Crea el registro de despacho para un pedido listo (idempotente). Respeta retiro/domicilio del vendedor. */
    public static function crearDespachoSiCorresponde($pedido): void
    {
        try {
            $pid = $pedido->id;
            if (DB::table('despachos')->where('pedido_id', $pid)->exists()) return;
            $ped = DB::table('pedidos')->where('id', $pid)->first();
            $retira = (bool) ($ped->retira_local ?? false);
            $folio = class_exists(\App\Services\Folios::class) ? \App\Services\Folios::next('DES') : ('DES-' . str_pad((string) $pid, 6, '0', STR_PAD_LEFT));
            $data = [
                'pedido_id'        => $pid,
                'ruta'             => $retira ? 'retiro_local' : 'transportista',
                'local_retiro_id'  => $retira ? ($ped->local_id ?? null) : null,
                'estado'           => 'programado',
                'listo'            => false,
                'fecha_programada' => $ped->fecha_comprometida ?? $ped->fecha_solicitada ?? null,
                'folio'            => $folio,
                'created_at'       => now(),
                'updated_at'       => now(),
            ];
            DB::table('despachos')->insert($data);
        } catch (\Throwable $e) { report($e); }
    }
}
