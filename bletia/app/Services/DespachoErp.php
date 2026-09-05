<?php

namespace App\Services;

use App\Mail\DocumentoPedido;
use App\Models\Despacho;
use App\Models\LinkUnico;
use App\Models\Confirmacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class DespachoErp
{
    protected static function pedido(int $id) { return DB::table('pedidos')->where('id', $id)->first(); }

    protected static function cliente($pedido): array
    {
        $c = null;
        if ($pedido && isset($pedido->cliente_id) && Schema::hasTable('clientes')) {
            $c = DB::table('clientes')->where('id', $pedido->cliente_id)->first();
        }
        $v = fn ($keys, $d = '') => collect((array) $keys)->map(fn ($k) => $c->$k ?? null)->filter()->first() ?: $d;
        return [
            'nombre'  => $v(['nombre', 'nombres'], 'Cliente'),
            'email'   => $v(['email']),
            'celular' => $v(['celular', 'telefono']),
        ];
    }

    protected static function dueno(): string
    {
        return (class_exists(\App\Models\Ajuste::class) ? (\App\Models\Ajuste::get('erp_email_dueno') ?: null) : null)
            ?: config('mail.from.address');
    }

    protected static function correoGuias(): ?string
    {
        return class_exists(\App\Models\Ajuste::class) ? (\App\Models\Ajuste::get('erp_email_guias') ?: null) : null;
    }

    protected static function marca(): string { return config('tienda.marca', config('app.name')); }

    protected static function wrap(string $titulo, string $cuerpo): string
    {
        return \App\Support\CorreoBrand::wrap($titulo, $cuerpo);
    }

    protected static function enriquecer(Despacho $d): Despacho
    {
        if ($d->transportista_id) {
            $nombre = DB::table('transportistas')->where('id', $d->transportista_id)->value('nombre');
            $d->setRawAttributes(array_merge($d->getAttributes(), ['transportista_nombre' => $nombre]), true);
        }
        return $d;
    }

    public static function documentos(Despacho $d): array
    {
        $p = self::pedido($d->pedido_id);
        if (! $p) return [];
        self::enriquecer($d);
        if ($d->ruta === 'transportista') {
            return [
                'guia'      => PdfErp::guiaRemision($p, $d),
                'etiquetas' => PdfErp::etiquetasBultos($p),
                'entrega'   => PdfErp::documentoEntrega($p),
            ];
        }
        return [
            'entrega'   => PdfErp::documentoEntrega($p),
            'etiquetas' => PdfErp::etiquetasBultos($p),
        ];
    }

    protected static function historial(int $pedidoId, ?string $de, string $a, string $notas = ''): void
    {
        if (! Schema::hasTable('historial_pedido')) return;
        DB::table('historial_pedido')->insert([
            'pedido_id' => $pedidoId, 'estado_anterior' => $de, 'estado_nuevo' => $a,
            'usuario_id' => optional(auth()->user())->id, 'notas' => $notas, 'creado_en' => now(),
        ]);
    }

    protected static function setEstado($pedido, string $nuevo, string $notas = ''): void
    {
        $de = $pedido->estado_erp ?? null;
        if (Schema::hasColumn('pedidos', 'estado_erp')) {
            DB::table('pedidos')->where('id', $pedido->id)->update(['estado_erp' => $nuevo]);
        }
        self::historial($pedido->id, $de, $nuevo, $notas);
    }

    public static function notificar(Despacho $d): array
    {
        \App\Services\Traza::registrar(\App\Models\PedidoEspecial::find($d->pedido_id), 'despachado');
        $p = self::pedido($d->pedido_id);
        if (! $p) return ['ok' => false, 'msg' => 'Pedido no encontrado'];

        $docs = self::documentos($d);
        $cli = self::cliente($p);
        $folio = $p->folio ?? ('#' . $p->id);

        $tipo = $d->ruta === 'transportista' ? 'transportista' : 'cliente_retiro';
        $link = LinksErp::crear($tipo, $p->id, $d->id);
        $d->update(['link_id' => $link->id, 'estado' => 'en_transito']);
        $urlConfirm = LinksErp::url($link);
        $urlSeguir = url('/seguimiento?p=' . $p->id);

        $enviados = [];

        if ($cli['email']) {
            $cuerpo = '<p>Hola ' . e($cli['nombre']) . ', tu pedido <strong>' . $folio . '</strong> está en camino.</p>'
                . '<p>Sigue su estado:<br><a href="' . $urlSeguir . '">' . $urlSeguir . '</a></p>';
            $adj = isset($docs['entrega']) ? [$docs['entrega']] : [];
            try { Mail::to($cli['email'])->send(new DocumentoPedido('Tu pedido ' . $folio . ' va en camino', self::wrap('Pedido en camino', $cuerpo), $adj)); $enviados[] = 'cliente'; }
            catch (\Throwable $e) { report($e); }
        }

        if ($d->ruta === 'transportista' && $d->transportista_id) {
            $tr = DB::table('transportistas')->where('id', $d->transportista_id)->first();
            if ($tr && ! empty($tr->email)) {
                $cuerpo = '<p>Nueva entrega del pedido <strong>' . $folio . '</strong> para ' . e($cli['nombre']) . '.</p>'
                    . '<p style="margin:6px 0"><strong>Fabricado por:</strong> ' . \App\Services\Traza::textoQuien(\App\Models\PedidoEspecial::find($d->pedido_id), 'enviado_fabricacion') . '</p>'
                    . '<p>Al entregar, confirma y sube 2 fotos:<br><a href="' . $urlConfirm . '">' . $urlConfirm . '</a></p>';
                $adj = array_values(array_filter([$docs['guia'] ?? null, $docs['etiquetas'] ?? null]));
                try { Mail::to($tr->email)->send(new DocumentoPedido('Entrega pedido ' . $folio, self::wrap('Orden de entrega', $cuerpo), $adj)); $enviados[] = 'transportista'; }
                catch (\Throwable $e) { report($e); }
            }
        }

        if ($d->ruta === 'retiro_local' && $cli['email']) {
            $cuerpo = '<p>Tu pedido <strong>' . $folio . '</strong> está listo para retiro.</p>'
                . '<p>Al retirarlo, confirma y sube 2 fotos:<br><a href="' . $urlConfirm . '">' . $urlConfirm . '</a></p>';
            try { Mail::to($cli['email'])->send(new DocumentoPedido('Pedido ' . $folio . ' listo para retiro', self::wrap('Listo para retiro', $cuerpo), [])); $enviados[] = 'cliente_retiro'; }
            catch (\Throwable $e) { report($e); }
        }

        // Copia de la GUÍA al correo fijo de guías
        $guias = self::correoGuias();
        if ($guias && ! empty($docs['guia'])) {
            $cuerpo = '<p>Guía del despacho del pedido <strong>' . $folio . '</strong> (ruta ' . $d->ruta . ').</p>'
                . '<p style="margin:6px 0"><strong>Despachado por:</strong> ' . \App\Services\Traza::textoQuien($d->pedido ?? \App\Models\PedidoEspecial::find($d->pedido_id), 'despachado') . '</p>';
            try { Mail::to($guias)->send(new DocumentoPedido('Guía · pedido ' . $folio, self::wrap('Guía de remisión', $cuerpo), [$docs['guia']])); $enviados[] = 'guias'; }
            catch (\Throwable $e) { report($e); }
        }

        try {
            Mail::to(self::dueno())->send(new DocumentoPedido(
                'Despacho pedido ' . $folio . ' (' . $d->ruta . ')',
                self::wrap('Despacho generado', '<p>Pedido ' . $folio . ' — ruta ' . $d->ruta . '. Confirmación: <a href="' . $urlConfirm . '">enlace</a></p>'),
                []
            ));
        } catch (\Throwable $e) { report($e); }

        self::setEstado($p, 'despachado', 'Despacho ' . $d->ruta);
        return ['ok' => true, 'enviados' => $enviados, 'confirm' => $urlConfirm];
    }

    public static function alConfirmar(LinkUnico $link, ?Confirmacion $conf = null): void
    {
        // PROVEEDOR: confirma fabricación → pedido a listo_despacho (no entrega)
        if ($link->tipo === 'proveedor') {
            $p = $link->pedido_id ? self::pedido($link->pedido_id) : null;
            if ($p) { \App\Services\EstadoPedidoErp::avanzar($p, 'listo_despacho'); }
            try {
                $folio = $p->folio ?? ('#' . ($p->id ?? ''));
                Mail::to(self::dueno())->send(new DocumentoPedido(
                    'Fabricación confirmada · pedido ' . $folio,
                    self::wrap('Fabricación confirmada', '<p>El proveedor confirmó el pedido ' . $folio . ' como terminado. Está listo para despacho.</p>'),
                    []
                ));
            } catch (\Throwable $e) { report($e); }
            return;
        }
        // ENTREGA (transportista / cliente_retiro): marca entregado
        if (! $link->despacho_id) return;
        $d = Despacho::find($link->despacho_id);
        if (! $d) return;
        $d->update(['estado' => 'entregado']);
        $p = self::pedido($d->pedido_id);
        if ($p) self::setEstado($p, 'entregado', 'Confirmado vía link ' . $link->tipo);

        try {
            $folio = $p->folio ?? ('#' . ($p->id ?? ''));
            $foto = $conf?->foto_1_url ? '<p><img src="' . url($conf->foto_1_url) . '" style="max-width:280px"></p>' : '';
            Mail::to(self::dueno())->send(new DocumentoPedido(
                'Entrega confirmada · pedido ' . $folio,
                self::wrap('Entrega confirmada', '<p>El pedido ' . $folio . ' fue confirmado (' . $link->tipo . ').</p>'
                    . ($conf && $conf->receptor_nombre ? '<p>Recibió: ' . e($conf->receptor_nombre) . '</p>' : '') . $foto),
                []
            ));
        } catch (\Throwable $e) { report($e); }
    }
}
