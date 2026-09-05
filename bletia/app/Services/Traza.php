<?php
namespace App\Services;

use App\Models\PedidoHistorial;
use App\Support\Acl;
use Illuminate\Support\Facades\Auth;

/**
 * Registro de trazabilidad por pedido. Escribe historial + campos fijos.
 */
class Traza
{
    /** Registra un evento en el historial y actualiza el campo "quién" correspondiente del pedido. */
    public static function registrar($pedido, string $accion, ?string $nota = null): void
    {
        $u = Auth::user();
        PedidoHistorial::create([
            'pedido_id'   => $pedido->id,
            'accion'      => $accion,
            'user_id'     => $u?->id,
            'user_nombre' => $u?->name,
            'rol'         => $u ? ($u->rol ?: 'admin') : null,
            'nota'        => $nota,
            'created_at'  => now(),
        ]);

        // mapear acción -> par de columnas (por / at) en pedidos
        $map = [
            'vendido'             => ['vendido_por', 'vendido_at'],
            'aprobado'            => ['aprobado_por', 'aprobado_at'],
            'enviado_fabricacion' => ['enviado_fab_por', 'enviado_fab_at'],
            'despachado'          => ['despachado_por', 'despachado_por_at'],
        ];
        if (isset($map[$accion]) && $u) {
            [$cPor, $cAt] = $map[$accion];
            $pedido->forceFill([$cPor => $u->id, $cAt => now()])->save();
        }
    }

    /** Nombre + rol + fecha de quien hizo una acción (para correos/vistas). null si no hay. */
    public static function quien($pedido, string $accion): ?array
    {
        $h = PedidoHistorial::where('pedido_id', $pedido->id)->where('accion', $accion)->latest('id')->first();
        if (! $h) return null;
        return [
            'nombre' => $h->user_nombre ?: '—',
            'rol'    => Acl::ROLES[$h->rol] ?? ($h->rol ?: '—'),
            'fecha'  => $h->created_at?->format('d/m/Y H:i'),
        ];
    }

    public static function textoQuien($pedido, string $accion): string
    {
        $q = self::quien($pedido, $accion);
        return $q ? "{$q['nombre']} ({$q['rol']}) · {$q['fecha']}" : '—';
    }
}
