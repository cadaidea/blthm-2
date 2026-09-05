<?php

namespace App\Services;

use App\Models\Recibo;
use App\Models\PedidoEspecial;
use Illuminate\Support\Facades\DB;

/**
 * Gestiona el ciclo de vida de un cheque recibido como pago.
 * Estados: pendiente | cobrado | rechazado | anulado.
 * Regla: un cheque rechazado/anulado deja de contar como pago (validado=0),
 * por lo que el saldo del pedido vuelve a subir y se bloquea la entrega.
 * Nada se borra: el recibo conserva su registro con su nuevo estado (regla de oro).
 */
class ChequeTesoreria
{
    /** Marca el cheque como cobrado/depositado. El pago se mantiene. */
    public static function cobrar(Recibo $r): void
    {
        $r->update([
            'cheque_estado'     => 'cobrado',
            'cheque_cobrado'    => true,
            'cheque_cobrado_at' => now(),
            'cheque_motivo'     => null,
        ]);
        self::traza($r, 'cheque_cobrado', 'Cheque N° ' . ($r->cheque_numero ?: '—') . ' cobrado');
        self::bitacora('cobró cheque', $r);
    }

    /** El cheque rebotó / sin fondos: deja de contar como pago. El saldo sube. */
    public static function rechazar(Recibo $r, ?string $motivo = null): void
    {
        $r->update([
            'cheque_estado'  => 'rechazado',
            'cheque_cobrado' => false,
            'validado'       => false,   // <-- deja de contar en el saldo
            'cheque_motivo'  => $motivo,
        ]);
        self::recalcularEntrega($r);
        self::traza($r, 'cheque_rechazado', 'Cheque N° ' . ($r->cheque_numero ?: '—') . ' RECHAZADO. ' . ($motivo ?: ''));
        self::bitacora('rechazó cheque (sin fondos)', $r);
    }

    /** Anula el cheque (regla de oro: no se borra). Deja de contar como pago. */
    public static function anular(Recibo $r, ?string $motivo = null): void
    {
        $r->update([
            'cheque_estado'  => 'anulado',
            'cheque_cobrado' => false,
            'validado'       => false,   // <-- deja de contar en el saldo
            'cheque_motivo'  => $motivo,
        ]);
        self::recalcularEntrega($r);
        self::traza($r, 'cheque_anulado', 'Cheque N° ' . ($r->cheque_numero ?: '—') . ' anulado. ' . ($motivo ?: ''));
        self::bitacora('anuló cheque', $r);
    }

    /**
     * Cambio de cheque: anula el viejo y registra uno nuevo (regla de oro).
     * El nuevo nace validado, por lo que el saldo se mantiene si cubre lo mismo.
     */
    public static function cambiar(Recibo $viejo, array $datos): Recibo
    {
        // 1) crear el recibo del cheque nuevo (espeja el viejo, con los datos nuevos)
        $nuevo = Recibo::create([
            'pedido_id'           => $viejo->pedido_id,
            'cliente_id'          => $viejo->cliente_id,
            'tipo'                => $viejo->tipo,
            'monto'               => $datos['monto'] ?? $viejo->monto,
            'metodo'              => 'cheque',
            'fecha'               => now()->toDateString(),
            'validado'            => true,
            'validado_por'        => auth()->id(),
            'validado_at'         => now(),
            'cheque_girador'      => $datos['cheque_girador'] ?? $viejo->cheque_girador,
            'cheque_numero'       => $datos['cheque_numero'] ?? null,
            'cheque_banco'        => $datos['cheque_banco'] ?? $viejo->cheque_banco,
            'cheque_fecha_cobro'  => $datos['cheque_fecha_cobro'] ?? null,
            'cheque_estado'       => 'pendiente',
            'pagador_nombre'      => $viejo->pagador_nombre,
            'pagador_email'       => $viejo->pagador_email,
            'nota'                => 'Reemplaza al cheque ' . ($viejo->cheque_numero ?: ('recibo #' . $viejo->id)),
        ]);

        // 2) anular el viejo y vincularlo al nuevo
        $viejo->update([
            'cheque_estado'       => 'anulado',
            'cheque_cobrado'      => false,
            'validado'            => false,
            'cheque_motivo'       => 'Reemplazado por cheque ' . ($datos['cheque_numero'] ?? ('recibo #' . $nuevo->id)),
            'cheque_reemplazo_id' => $nuevo->id,
        ]);

        self::recalcularEntrega($viejo);
        self::traza($viejo, 'cheque_cambiado', 'Cheque ' . ($viejo->cheque_numero ?: '#'.$viejo->id) . ' reemplazado por ' . ($nuevo->cheque_numero ?: '#'.$nuevo->id));
        self::bitacora('cambió cheque', $viejo);
        return $nuevo;
    }

    /** Si por el cambio de saldo el pedido ya no está pagado, retrocede de entregado/listo. */
    protected static function recalcularEntrega(Recibo $r): void
    {
        $ped = PedidoEspecial::find($r->pedido_id);
        if (! $ped) return;
        // el saldo se recalcula solo (pagado() solo cuenta validados).
        // si quedó saldo pendiente y el despacho aún no salió, el bloqueo por saldo actúa solo.
        try {
            \App\Services\Traza::registrar($ped, 'saldo_actualizado', 'Saldo recalculado tras novedad de cheque. Pendiente: $' . number_format(\App\Services\RecibosErp::saldo($ped), 2));
        } catch (\Throwable $e) { report($e); }
    }

    protected static function traza(Recibo $r, string $evento, string $detalle): void
    {
        try {
            $ped = PedidoEspecial::find($r->pedido_id);
            if ($ped) \App\Services\Traza::registrar($ped, $evento, $detalle);
        } catch (\Throwable $e) { report($e); }
    }

    protected static function bitacora(string $accion, Recibo $r): void
    {
        try {
            if (class_exists(\App\Models\Bitacora::class)) {
                \App\Models\Bitacora::registrar($accion, 'Recibo', $r->id, 'Cheque N° ' . ($r->cheque_numero ?: '—') . ' · $' . number_format((float) $r->monto, 2));
            }
        } catch (\Throwable $e) { report($e); }
    }
}
