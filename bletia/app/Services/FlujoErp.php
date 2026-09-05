<?php
namespace App\Services;

use App\Models\PedidoEspecial;
use App\Support\Acl;
use App\Support\CorreoBrand;
use App\Mail\DocumentoPedido;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/**
 * Flujo automatico estilo Amazon:
 * - aprobar(): valida proveedor + fecha comprometida -> envia a fabricacion + notifica vendedor y cliente
 * - cambiarFecha(): actualiza fecha comprometida -> notifica vendedor y cliente
 * - alarmaPorAprobar(): correo a dueño/operaciones cuando entra un nuevo pendiente
 */
class FlujoErp
{
    /** Aprueba y dispara fabricacion + notificaciones. Devuelve [ok, msg]. */
    public static function aprobar(PedidoEspecial $p, string $destino = 'proveedor'): array
    {
        $hayItems = DB::table('pedido_items')->where('pedido_id', $p->id)->exists();
        if (! $hayItems) return ['ok' => false, 'msg' => 'El pedido no tiene ítems.'];
        // No enviar a fabricación sin anticipo confirmado (pago/abono validado)
        if (\App\Services\RecibosErp::pagado($p) <= 0) {
            return ['ok' => false, 'msg' => 'No se puede enviar a fabricación sin anticipo confirmado. Registra o valida un cobro primero.'];
        }
        // proveedor externo requiere proveedor en items; interno no
        if ($destino === 'proveedor') {
            $sinProv = DB::table('pedido_items')->where('pedido_id', $p->id)
                ->where(function ($q) { $q->whereNull('proveedor_id')->orWhere('proveedor_id', 0); })->exists();
            if ($sinProv) return ['ok' => false, 'msg' => 'Asigna proveedor a todos los ítems antes de aprobar.'];
        }

        // 2) fecha: si no hay comprometida, usar la solicitada del cliente
        if (Schema::hasColumn('pedidos', 'fecha_comprometida') && empty($p->fecha_comprometida) && ! empty($p->fecha_solicitada)) {
            DB::table('pedidos')->where('id', $p->id)->update(['fecha_comprometida' => $p->fecha_solicitada]);
            $p->refresh();
        }

        // 3) marcar aprobado + traza
        EstadoPedidoErp::avanzar($p, 'aprobado', false);
        Traza::registrar($p, 'aprobado');

        // 4) marcar destino + enviar a fabricación según corresponda
        DB::table('pedidos')->where('id', $p->id)->update(['destino_fab' => $destino]);
        Traza::registrar($p, 'enviado_fabricacion');
        if ($destino === 'interno') {
            $res = self::enviarAProduccion($p->fresh());
        } else {
            $res = EstadoPedidoErp::enviarAProveedor($p->fresh());
        }

        // 5) notificar al vendedor: aprobado + en fabricacion
        self::avisarVendedor($p->fresh());

        // (el aviso al cliente "en fabricación" lo dispara avanzar() dentro de enviarAProveedor)
        return ['ok' => true, 'msg' => 'Aprobado y enviado a fabricación.', 'detalle' => $res];
    }

    /** Cambia la fecha comprometida y notifica a vendedor y cliente. */
    public static function cambiarFecha(PedidoEspecial $p, string $nuevaFecha, ?string $motivo = null): array
    {
        DB::table('pedidos')->where('id', $p->id)->update(['fecha_comprometida' => $nuevaFecha]);
        $p->refresh();
        Traza::registrar($p, 'cambio_fecha', 'Nueva fecha: ' . $nuevaFecha . ($motivo ? ' · ' . $motivo : ''));

        $num = EstadoPedidoErp::numero($p);
        $f = \Illuminate\Support\Carbon::parse($nuevaFecha)->format('d/m/Y');
        $cuerpo = '<p>La fecha de entrega del pedido <strong>' . $num . '</strong> se actualizó a <strong>' . $f . '</strong>.</p>'
            . ($motivo ? '<p>Motivo: ' . e($motivo) . '</p>' : '');
        $html = CorreoBrand::wrap('Actualización de fecha', $cuerpo);

        // cliente
        if ($to = EstadoPedidoErp::emailCliente($p)) {
            try { Mail::to($to)->send(new DocumentoPedido('Nueva fecha · pedido ' . $num, $html, [])); } catch (\Throwable $e) { report($e); }
        }
        // vendedor
        if ($em = self::emailVendedor($p)) {
            try { Mail::to($em)->send(new DocumentoPedido('[Interno] Nueva fecha · pedido ' . $num, $html, [])); } catch (\Throwable $e) { report($e); }
        }
        return ['ok' => true];
    }

    protected static function avisarVendedor(PedidoEspecial $p): void
    {
        if (! ($em = self::emailVendedor($p))) return;
        $num = EstadoPedidoErp::numero($p);
        $f = $p->fecha_comprometida ? \Illuminate\Support\Carbon::parse($p->fecha_comprometida)->format('d/m/Y') : null;
        $cuerpo = '<p>Tu pedido <strong>' . $num . '</strong> fue <strong>aprobado</strong> y enviado a fabricación.</p>'
            . ($f ? '<p>Fecha comprometida: <strong>' . $f . '</strong></p>' : '');
        $html = CorreoBrand::wrap('Pedido aprobado', $cuerpo);
        try { Mail::to($em)->send(new DocumentoPedido('Pedido aprobado ' . $num, $html, [])); } catch (\Throwable $e) { report($e); }
    }

    protected static function emailVendedor(PedidoEspecial $p): ?string
    {
        if (empty($p->vendedor_id)) return null;
        return DB::table('users')->where('id', $p->vendedor_id)->value('email');
    }

    /** Correo de alarma a dueño + operaciones cuando entra un pedido por aprobar. */
    public static function alarmaPorAprobar(PedidoEspecial $p): void
    {
        $dest = [];
        if ($d = EstadoPedidoErp::dueno()) $dest[] = $d;
        foreach (DB::table('users')->where('rol', 'operaciones')->where('activo', true)->pluck('email') as $e) $dest[] = $e;
        $dest = array_values(array_unique(array_filter($dest)));
        if (! $dest) return;

        $num = EstadoPedidoErp::numero($p);
        $cuerpo = '<p>Hay un nuevo pedido <strong>' . $num . '</strong> que requiere tu aprobación.</p>'
            . '<p>Ingresa al panel, asigna proveedor y fecha comprometida, y aprueba para enviarlo a fabricación.</p>';
        $html = CorreoBrand::wrap('Pedido por aprobar', $cuerpo);
        foreach ($dest as $to) {
            try { Mail::to($to)->send(new DocumentoPedido('Por aprobar · pedido ' . $num, $html, [])); } catch (\Throwable $e) { report($e); }
        }
    }

    /** Envía el pedido a Producción interna (taller): estado en_produccion + notifica al rol producción y operaciones. */
    public static function enviarAProduccion(PedidoEspecial $p): array
    {
        EstadoPedidoErp::avanzar($p, 'en_produccion');
        $num = EstadoPedidoErp::numero($p);
        $dest = [];
        foreach (DB::table('users')->whereIn('rol', ['produccion', 'operaciones'])->where('activo', true)->pluck('email') as $e) $dest[] = $e;
        $dest = array_values(array_unique(array_filter($dest)));
        $f = $p->fecha_comprometida ? \Illuminate\Support\Carbon::parse($p->fecha_comprometida)->format('d/m/Y') : null;
        $cuerpo = '<p>El pedido <strong>' . $num . '</strong> fue asignado a <strong>producción interna (taller)</strong>.</p>'
            . ($f ? '<p>Fecha comprometida: <strong>' . $f . '</strong></p>' : '')
            . '<p>Ingresa al panel de Producción para ver el detalle y gestionar materiales.</p>';
        $html = CorreoBrand::wrap('Nuevo pedido en producción', $cuerpo);
        foreach ($dest as $to) {
            try { Mail::to($to)->send(new DocumentoPedido('Producción · pedido ' . $num, $html, [])); } catch (\Throwable $e) { report($e); }
        }
        return ['ok' => true, 'destino' => 'interno', 'avisados' => $dest];
    }

    /** Reasigna proveedor (si el actual no cumple): cambia proveedor en ítems, reenvía OF, notifica partes. */
    public static function reasignarProveedor(PedidoEspecial $p, int $proveedorId): array
    {
        DB::table('pedido_items')->where('pedido_id', $p->id)->update(['proveedor_id' => $proveedorId]);
        Traza::registrar($p, 'reasignado_proveedor', 'Nuevo proveedor id ' . $proveedorId);
        // reenviar OF al nuevo proveedor + marcar enviado_proveedor
        $res = EstadoPedidoErp::enviarAProveedor($p->fresh());
        // avisar vendedor
        self::avisarVendedor($p->fresh());
        return ['ok' => true, 'msg' => 'Proveedor reasignado y OF reenviada.', 'detalle' => $res];
    }
}
