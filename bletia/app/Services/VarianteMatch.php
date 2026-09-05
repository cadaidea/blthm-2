<?php
namespace App\Services;

use App\Models\Producto;
use App\Models\Variante;
use App\Models\Atributo;
use App\Models\AtributoOpcion;
use Illuminate\Support\Facades\Storage;

/**
 * Dada la combinación de opciones elegidas (opt_atributos[atributo_id] = opcion_id),
 * encuentra la variante que coincide y setea: precio (pvp), foto_preview (img),
 * tapiz_principal (Tapiz), lacado (Madera), variante_id y el detalle combinado.
 */
class VarianteMatch
{
    public static function aplicar($get, $set): void
    {
        $pid = $get('producto_id');
        $sel = (array) ($get('opt_atributos') ?: []);
        $sel = array_filter($sel, fn ($v) => filled($v));
        if (! $pid) return;

        $producto = Producto::with('variantes')->find($pid);
        if (! $producto) return;

        // buscar variante cuya 'opciones' contenga exactamente las opciones elegidas
        $match = null;
        foreach ($producto->variantes as $v) {
            $op = collect((array) ($v->opciones ?: []))->filter(fn ($x) => filled($x))
                ->mapWithKeys(fn ($oid, $aid) => [(int) $aid => (int) $oid])->all();
            if (! $op) continue;
            // todas las opciones elegidas deben coincidir con las de la variante
            $coincide = true;
            foreach ($sel as $aid => $oid) {
                if (($op[(int) $aid] ?? null) !== (int) $oid) { $coincide = false; break; }
            }
            // y la variante no debe tener atributos extra sin elegir
            if ($coincide && count($op) === count($sel)) { $match = $v; break; }
        }

        if (! $match) return;

        // precio
        $set('precio', $match->pvp_final);
        $set('variante_id', $match->id);

        // foto preview (url pública)
        $url = $match->foto ? Storage::disk('public')->url($match->foto) : null;
        $set('foto_preview', $url);

        // llenar campos por atributo: Tapiz -> tapiz_principal, Madera -> lacado
        $ops = AtributoOpcion::with('atributo')->whereIn('id', array_values($sel))->get();
        foreach ($ops as $o) {
            $nombreAttr = strtolower($o->atributo?->nombre ?? '');
            if (str_contains($nombreAttr, 'tapiz'))  $set('tapiz_principal', $o->valor);
            if (str_contains($nombreAttr, 'madera') || str_contains($nombreAttr, 'lacado')) $set('lacado', $o->valor);
        }
    }
}
