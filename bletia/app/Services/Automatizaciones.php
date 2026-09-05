<?php

namespace App\Services;

use App\Models\Automatizacion;
use App\Models\AutomatizacionRun;
use App\Models\Campania;
use App\Models\Suscriptor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class Automatizaciones
{
    /** ¿ya se ejecutó esta automatización para este objeto? (idempotencia) */
    protected static function yaCorrio(int $autoId, int $objId, string $tipo): bool
    {
        return AutomatizacionRun::where('automatizacion_id', $autoId)
            ->where('objeto_id', $objId)->where('objeto_tipo', $tipo)->exists();
    }

    protected static function marcar(int $autoId, int $objId, string $tipo, ?int $campId = null): void
    {
        AutomatizacionRun::firstOrCreate(
            ['automatizacion_id' => $autoId, 'objeto_id' => $objId, 'objeto_tipo' => $tipo],
            ['campania_id' => $campId, 'created_at' => now()]
        );
    }

    protected static function activas(string $tipo)
    {
        return Automatizacion::where('tipo', $tipo)->where('estado', 'activa')->get();
    }

    /** Crea una campaña broadcast a las listas de la automatización (usa el motor Digest existente). */
    protected static function campanaDesde(Automatizacion $a, string $asunto, string $html, string $objTipo, int $objId): void
    {
        $c = Campania::create([
            'asunto'      => $asunto,
            'preheader'   => $a->preheader,
            'cuerpo_html' => $html,
            'lista_ids'   => $a->lista_ids,
            'estado'      => 'borrador',
        ]);
        \App\Services\Digest::encolar($c); // pasa a 'enviando'; el cron procesa lotes
        self::marcar($a->id, $objId, $objTipo, $c->id);
        $a->update(['last_run_at' => now()]);
    }

    /** Envío directo (transaccional) a un email con branding + variables. */
    protected static function enviarDirecto(string $email, string $asunto, string $html, array $vars = []): void
    {
        foreach ($vars as $k => $v) $html = str_replace($k, (string) $v, $html);
        $wrapped = \App\Support\CorreoBrand::wrap($asunto, $html);
        try {
            Mail::html($wrapped, fn ($m) => $m->to($email)->subject($asunto));
        } catch (\Throwable $e) { report($e); }
    }

    // ============ TRIGGERS DE TIENDA ============

    /** Llamar cuando se publica un artículo nuevo. */
    public static function postPublish($articulo): void
    {
        foreach (self::activas('post_publish') as $a) {
            if (self::yaCorrio($a->id, (int) $articulo->id, 'post')) continue;
            $vars = ['{post_title}' => $articulo->titulo ?? '', '{post_url}' => $articulo->url ?? ''];
            $html = strtr($a->cuerpo_html ?: '<p>{post_title}</p><p><a href="{post_url}">Leer</a></p>', $vars);
            $asunto = strtr($a->asunto ?: ($articulo->titulo ?? 'Nueva publicación'), $vars);
            self::campanaDesde($a, $asunto, $html, 'post', (int) $articulo->id);
        }
    }

    /** Llamar 1h tras carrito sin checkout. $order: objeto con cliente/email + items. */
    public static function abandonedCart($order): void
    {
        foreach (self::activas('abandoned_cart') as $a) {
            if (self::yaCorrio($a->id, (int) $order->id, 'order')) continue;
            $email = $order->email ?? optional($order->cliente)->email;
            if (! $email) continue;
            $vars = [
                '{first_name}'        => $order->nombre ?? optional($order->cliente)->nombre ?? '',
                '{cart_items_count}'  => method_exists($order, 'items') ? $order->items()->count() : ($order->items_count ?? ''),
                '{cart_recovery_url}' => $order->recovery_token ? url('/cart/recover/' . $order->recovery_token) : url('/carrito'),
            ];
            self::enviarDirecto($email, $a->asunto ?: 'Dejaste algo en tu carrito', $a->cuerpo_html ?: '<p>Hola {first_name}, tu carrito te espera.</p>', $vars);
            self::marcar($a->id, (int) $order->id, 'order');
        }
    }

    /** Cuando un producto pasa de stock 0 a >0. */
    public static function backInStock(int $productoId, string $nombre, float $precio, string $url): void
    {
        $autos = self::activas('back_in_stock');
        if ($autos->isEmpty()) return;
        $pendientes = DB::table('avisos_stock')->where('producto_id', $productoId)->where('notificado', false)->get();
        foreach ($pendientes as $av) {
            foreach ($autos as $a) {
                $vars = ['{product_name}' => $nombre, '{product_price}' => number_format($precio, 2), '{product_url}' => $url, '{first_name}' => ''];
                self::enviarDirecto($av->email, $a->asunto ?: ('Volvió: ' . $nombre), $a->cuerpo_html ?: '<p>{product_name} ya está disponible. <a href="{product_url}">Verlo</a></p>', $vars);
            }
            DB::table('avisos_stock')->where('id', $av->id)->update(['notificado' => true, 'notificado_at' => now()]);
        }
    }

    /** Secuencia post-compra (día 0/7/30). Llamar diario desde el command. */
    public static function postPurchaseDiario(): void
    {
        foreach (self::activas('post_purchase') as $a) {
            $sec = $a->opciones['secuencia'] ?? [['dia' => 0, 'asunto' => 'Gracias por tu compra', 'html' => '<p>Gracias, {first_name}.</p>']];
            // El enganche concreto a la tabla de pedidos lo define el usuario; aquí queda el motor listo.
            $a->update(['last_run_at' => now()]);
        }
    }

    /** Win-back: clientes/suscriptores sin actividad en N días. Llamar diario. */
    public static function winbackDiario(): void
    {
        foreach (self::activas('winback') as $a) {
            $dias = (int) ($a->opciones['dias'] ?? 90);
            $limite = now()->subDays($dias);
            $subs = Suscriptor::where('estado', 'confirmado')
                ->where(function ($q) use ($limite) {
                    $q->whereNull('updated_at')->orWhere('updated_at', '<', $limite);
                })->limit(200)->get();
            foreach ($subs as $s) {
                if (self::yaCorrio($a->id, (int) $s->id, 'winback_' . now()->format('Ym'))) continue;
                self::enviarDirecto($s->email, $a->asunto ?: 'Te extrañamos', $a->cuerpo_html ?: '<p>Hola {first_name}, vuelve a vernos.</p>', ['{first_name}' => $s->nombre ?? '']);
                self::marcar($a->id, (int) $s->id, 'winback_' . now()->format('Ym'));
            }
            $a->update(['last_run_at' => now()]);
        }
    }

    /** Cumpleaños (subscriber_meta birthday) o digest — placeholders del motor diario. */
    public static function digestDiario(): void { foreach (self::activas('digest_daily') as $a) { $a->update(['last_run_at' => now()]); } }
    public static function digestSemanal(): void { foreach (self::activas('digest_weekly') as $a) { $a->update(['last_run_at' => now()]); } }

    /** Cumpleaños del día: envía a quienes cumplen hoy (campo nacimiento). */
    public static function cumpleanosDiario(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('suscriptores', 'nacimiento')) return;
        foreach (self::activas('birthday') as $a) {
            $hoy = now();
            $subs = Suscriptor::where('estado', 'confirmado')
                ->whereNotNull('nacimiento')
                ->whereMonth('nacimiento', $hoy->month)
                ->whereDay('nacimiento', $hoy->day)
                ->limit(300)->get();
            foreach ($subs as $s) {
                if (self::yaCorrio($a->id, (int) $s->id, 'bday_' . $hoy->format('Y'))) continue;
                self::enviarDirecto($s->email, $a->asunto ?: '¡Feliz cumpleaños!', $a->cuerpo_html ?: '<p>Feliz cumpleaños, {first_name} 🎉</p>', ['{first_name}' => $s->nombre ?? '']);
                self::marcar($a->id, (int) $s->id, 'bday_' . $hoy->format('Y'));
            }
            $a->update(['last_run_at' => now()]);
        }
    }
}
