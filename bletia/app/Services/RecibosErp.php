<?php
namespace App\Services;

use App\Mail\DocumentoPedido;
use App\Support\CorreoBrand;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class RecibosErp
{
    /** Pagado = suma de recibos VALIDADOS (un pago no cuenta hasta que el dueño lo valida). */
    public static function pagado($pedido): float
    {
        if (! Schema::hasTable('recibos')) return 0.0;
        $q = DB::table('recibos')->where('pedido_id', $pedido->id);
        if (Schema::hasColumn('recibos', 'validado')) $q->where('validado', true);
        return (float) $q->sum('monto');
    }

    public static function saldo($pedido): float
    {
        return round((float) ($pedido->total ?? 0) - self::pagado($pedido), 2);
    }

    /** Igual que pagado()/saldo() pero para una VENTA DIRECTA (sin pedido). */
    public static function pagadoVenta($venta): float
    {
        if (! Schema::hasTable("recibos")) return 0.0;
        return (float) DB::table("recibos")->where("venta_id", $venta->id)->where("validado", true)->sum("monto");
    }

    public static function saldoVenta($venta): float
    {
        return round((float) ($venta->total ?? 0) - self::pagadoVenta($venta), 2);
    }

    /** 'pagado' si saldo<=0 (con pagos validados), si no 'pendiente'. */
    public static function estadoPago($pedido): string
    {
        return self::saldo($pedido) <= 0 ? 'pagado' : 'pendiente';
    }

    protected static function emailCliente($pedido): ?string
    {
        if (empty($pedido->cliente_id) || ! Schema::hasTable('clientes')) return null;
        return DB::table('clientes')->where('id', $pedido->cliente_id)->value('email');
    }

    protected static function dueno(): ?string
    {
        $v = class_exists(\App\Models\Ajuste::class) ? (\App\Models\Ajuste::get('erp_email_dueno') ?: null) : null;
        return $v ?: config('mail.from.address');
    }

    protected static function contabilidad(): ?string
    {
        $v = class_exists(\App\Models\Ajuste::class) ? (\App\Models\Ajuste::get('erp_email_contabilidad') ?: null) : null;
        return $v ?: null;
    }

    protected static function url($pedido): string
    {
        try { return route('erp.seguimiento', ['p' => $pedido->id]); }
        catch (\Throwable $e) { return rtrim(config('app.url'), '/') . '/seguimiento?p=' . $pedido->id; }
    }

    protected static function fotosComprobante($recibo): array
    {
        $c = $recibo->comprobantes ?? null;
        if (is_string($c)) $c = json_decode($c, true);
        if (! is_array($c)) return [];
        $rutas = [];
        foreach ($c as $path) {
            $full = storage_path('app/public/' . ltrim($path, '/'));
            if (is_file($full)) $rutas[] = $full;
        }
        return $rutas;
    }

    /** Al REGISTRAR un recibo nuevo (sin validar): avisa al dueño para que valide. */
    public static function avisarValidacion($recibo, $pedido): void
    {
        $dueno = self::dueno();
        if (! $dueno) return;
        $n = $pedido->folio ?? $pedido->id;
        $monto = number_format((float) $recibo->monto, 2);
        $metodo = ucfirst((string) ($recibo->metodo ?? '—'));
        $cuerpo = '<p>Nuevo pago registrado para el pedido <strong>' . $n . '</strong> que requiere tu validación.</p>'
            . '<p><strong>Monto:</strong> $' . $monto . ' · <strong>Método:</strong> ' . $metodo . '</p>'
            . '<p>Ingresa al panel (Recibos) y pulsa <strong>Validar pago</strong> para confirmarlo.</p>';
        $html = CorreoBrand::wrap('Pago por validar · ' . $n, $cuerpo);
        $adj = self::fotosComprobante($recibo);
        try { Mail::to($dueno)->send(new DocumentoPedido('Pago por validar · pedido ' . $n, $html, $adj)); }
        catch (\Throwable $e) { report($e); }
    }

    /** El dueño VALIDA el pago: recalcula saldo, notifica cliente (detalle+método) y contabilidad (método+detalle+fotos). */
    public static function validar($recibo, $pedido): void
    {
        // registrar en la trazabilidad del pedido
        try {
            $monto = number_format((float) $recibo->monto, 2);
            $met = ucfirst((string) ($recibo->metodo ?? ''));
            $rec = $recibo->folio ?? ('#' . $recibo->id);
            \App\Services\Traza::registrar($pedido, 'pago_validado', $rec . ' · $' . $monto . ' · ' . $met);
        } catch (\Throwable $e) { report($e); }

        $saldo = self::saldo($pedido);
        $monto = number_format((float) $recibo->monto, 2);
        $metodo = ucfirst((string) ($recibo->metodo ?? '—'));
        $n = $pedido->folio ?? $pedido->id;
        $rec = $recibo->folio ?? null;
        $rotulo = $rec ? (' (' . $rec . ')') : '';

        // === CLIENTE: detalle + método ===
        if ($saldo <= 0) {
            $titulo = 'Pago confirmado · pedido ' . $n;
            $cuerpo = '<p>Confirmamos tu pago de <strong>$' . $monto . '</strong>' . $rotulo . ' por <strong>' . $metodo . '</strong>.</p>'
                . '<p>Tu pedido <strong>' . $n . '</strong> queda <strong>pagado en su totalidad</strong>. ¡Gracias!</p>';
        } else {
            $titulo = 'Abono confirmado · pedido ' . $n;
            $cuerpo = '<p>Confirmamos tu abono de <strong>$' . $monto . '</strong>' . $rotulo . ' por <strong>' . $metodo . '</strong>.</p>'
                . '<p>Saldo pendiente: <strong>$' . number_format($saldo, 2) . '</strong>.</p>';
        }
        $html = CorreoBrand::wrap($titulo, $cuerpo, [
            'preheader' => 'Estado de pago de tu pedido',
            'cta' => ['text' => 'Ver pedido', 'url' => self::url($pedido)],
        ]);
        if ($to = self::emailCliente($pedido)) {
            try { Mail::to($to)->send(new DocumentoPedido($titulo, $html, [])); } catch (\Throwable $e) { report($e); }
        }
        // copia al pagador si es distinto y dejó email
        $pagEmail = $recibo->pagador_email ?? null;
        if ($pagEmail && $pagEmail !== ($to ?? null)) {
            try { Mail::to($pagEmail)->send(new DocumentoPedido($titulo, $html, [])); } catch (\Throwable $e) { report($e); }
        }

        // === CONTABILIDAD: método + detalle + fotos comprobantes ===
        if ($contab = self::contabilidad()) {
            $det = '<p><strong>Pedido:</strong> ' . $n . '</p>'
                . '<p><strong>Recibo:</strong> ' . ($rec ?: '—') . ' · <strong>Tipo:</strong> ' . ucfirst((string) ($recibo->tipo ?? '—')) . '</p>'
                . '<p><strong>Monto:</strong> $' . $monto . ' · <strong>Método:</strong> ' . $metodo . '</p>'
                . '<p><strong>Saldo tras pago:</strong> $' . number_format($saldo, 2) . '</p>'
                . ($recibo->nota ? '<p><strong>Nota:</strong> ' . e($recibo->nota) . '</p>' : '');
            $htmlC = CorreoBrand::wrap('Pago validado · ' . $n, $det . '<p>Se adjuntan los comprobantes.</p>');
            $adj = self::fotosComprobante($recibo);
            try { Mail::to($contab)->send(new DocumentoPedido('Contabilidad · pago ' . $n, $htmlC, $adj)); }
            catch (\Throwable $e) { report($e); }
        }

        // copia dueño
        if (($dueno = self::dueno()) && $dueno !== ($to ?? null)) {
            try { Mail::to($dueno)->send(new DocumentoPedido('[Interno] ' . $titulo, $html, [])); } catch (\Throwable $e) { report($e); }
        }
    }

    /** Compat: notificar antiguo -> ahora dispara aviso de validación. */
    public static function notificar($recibo, $pedido): void
    {
        self::avisarValidacion($recibo, $pedido);
    }
}
