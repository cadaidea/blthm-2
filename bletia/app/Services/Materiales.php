<?php

namespace App\Services;

use App\Models\MateriaPrima;
use App\Mail\DocumentoPedido;
use App\Support\CorreoBrand;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class Materiales
{
    /** Materiales del producto (BOM) escalados por cantidad. Devuelve [materia_prima_id => cantidad]. */
    public static function bomDePedido($pedido): array
    {
        $need = [];
        if (! \Illuminate\Support\Facades\Schema::hasTable('producto_materiales')) return $need;
        $items = DB::table('pedido_items')->where('pedido_id', $pedido->id)->whereNotNull('producto_id')->get();
        foreach ($items as $it) {
            $cant = max(1, (int) $it->cantidad);
            $rows = DB::table('producto_materiales')->where('producto_id', $it->producto_id)->get();
            foreach ($rows as $r) {
                $need[$r->materia_prima_id] = ($need[$r->materia_prima_id] ?? 0) + (float) $r->cantidad * $cant;
            }
        }
        return $need;
    }

    /** Igual que bomDePedido() pero para una orden de producción interna (compra_items). */
    public static function bomDeCompra($compra): array
    {
        $need = [];
        if (! \Illuminate\Support\Facades\Schema::hasTable('producto_materiales')) return $need;
        $items = DB::table('compra_items')->where('compra_id', $compra->id)->whereNotNull('producto_id')->get();
        foreach ($items as $it) {
            $cant = max(1, (int) $it->cantidad);
            $rows = DB::table('producto_materiales')->where('producto_id', $it->producto_id)->get();
            foreach ($rows as $r) {
                $need[$r->materia_prima_id] = ($need[$r->materia_prima_id] ?? 0) + (float) $r->cantidad * $cant;
            }
        }
        return $need;
    }

    /** ¿Hay faltante de stock para fabricar el pedido según BOM? Devuelve lista de faltantes. */
    public static function faltantesPedido($pedido): array
    {
        $faltan = [];
        foreach (self::bomDePedido($pedido) as $mpId => $req) {
            $m = MateriaPrima::find($mpId);
            if (! $m) continue;
            $disp = (float) $m->stock;
            if ($disp < $req) {
                $faltan[] = ['materia' => $m->nombre, 'unidad' => $m->unidad, 'requiere' => $req, 'disponible' => $disp, 'falta' => round($req - $disp, 3)];
            }
        }
        return $faltan;
    }

    /** ¿Existe alguna solicitud de material no atendida para el pedido? */
    public static function tieneSolicitudAbierta($pedido): bool
    {
        return DB::table('movimientos_material')->where('pedido_id', $pedido->id)
            ->where('tipo', 'solicitud')->where('estado', 'solicitado')->exists();
    }

    /** Dispara alarma de faltante: dashboard + correo a Operaciones y Dueño. */
    public static function alarmaFaltante($pedido, array $faltan, ?string $contexto = null): void
    {
        if (! $faltan) return;
        $folio = $pedido->folio ?? $pedido->id;
        $lineas = array_map(fn ($f) => $f['materia'] . ': falta ' . $f['falta'] . ' ' . $f['unidad'] . ' (req ' . $f['requiere'] . ', hay ' . $f['disponible'] . ')', $faltan);

        // dashboard a operaciones/admin
        try {
            foreach (DB::table('users')->whereIn('rol', ['operaciones', 'admin'])->where('activo', true)->get() as $u) {
                if ($uu = \App\Models\User::find($u->id)) {
                    \Filament\Notifications\Notification::make()->danger()
                        ->title('Falta material · pedido ' . $folio)
                        ->body(implode(' | ', $lineas))->sendToDatabase($uu);
                }
            }
        } catch (\Throwable $e) { report($e); }

        // correo a operaciones + dueño
        $dest = DB::table('users')->whereIn('rol', ['operaciones', 'admin'])->where('activo', true)->pluck('email')->all();
        if (class_exists(\App\Models\Ajuste::class) && ($d = \App\Models\Ajuste::get('erp_email_dueno'))) $dest[] = $d;
        $dest = collect($dest)->filter()->unique()->all();
        $cuerpo = '<p>El pedido <strong>' . $folio . '</strong> no se puede completar por falta de material' . ($contexto ? ' (' . e($contexto) . ')' : '') . ':</p><ul>'
            . implode('', array_map(fn ($l) => '<li>' . e($l) . '</li>', $lineas)) . '</ul>'
            . '<p>Repongan el stock para continuar.</p>';
        $html = CorreoBrand::wrap('Falta material · ' . $folio, $cuerpo);
        foreach ($dest as $to) { try { Mail::to($to)->send(new DocumentoPedido('Falta material · ' . $folio, $html, [])); } catch (\Throwable $e) { report($e); } }
    }
}
