<?php

namespace App\Http\Controllers;

use App\Models\LinkUnico;
use App\Models\Reclamo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GarantiaController extends Controller
{
    public function show(string $token)
    {
        $link = LinkUnico::where('token', $token)->first();
        if (! $link || $link->tipo !== 'proveedor_garantia') return view('erp.confirmar.error', ['msg' => 'Enlace no válido.']);
        if ($link->usado) return view('erp.confirmar.error', ['msg' => 'Este enlace ya fue usado.']);
        if ($link->expira_en && now()->gt($link->expira_en)) return view('erp.confirmar.error', ['msg' => 'Este enlace expiró.']);
        $reclamo = Reclamo::with(['cliente', 'pedido'])->find($link->reclamo_id);
        if (! $reclamo) return view('erp.confirmar.error', ['msg' => 'Reclamo no encontrado.']);
        return view('erp.garantia.show', ['link' => $link, 'reclamo' => $reclamo]);
    }

    public function submit(Request $request, string $token)
    {
        $link = LinkUnico::where('token', $token)->first();
        if (! $link || $link->tipo !== 'proveedor_garantia' || $link->usado) {
            return view('erp.confirmar.error', ['msg' => 'Enlace no válido o ya usado.']);
        }
        $data = $request->validate([
            'foto_1' => 'required|image|max:8192',
            'foto_2' => 'required|image|max:8192',
        ]);
        $dir = 'garantias/' . $link->id;
        $f1 = $request->file('foto_1')->store($dir, 'public');
        $f2 = $request->file('foto_2')->store($dir, 'public');
        $link->update(['usado' => true, 'intentos' => $link->intentos + 1]);
        // actualizar reclamo: estado en_reparacion → listo para despacho (ya está reparado)
        $reclamo = Reclamo::find($link->reclamo_id);
        if ($reclamo) {
            $reclamo->update(['estado' => 'en_reparacion']);
            // notificar a operaciones
            try {
                $folio = $reclamo->folio ?: ('#'.$reclamo->id);
                \Illuminate\Support\Facades\DB::table('users')->whereIn('rol', ['admin','operaciones'])->where('activo', true)->get()
                    ->each(function ($u) use ($folio) {
                        \Filament\Notifications\Notification::make()->success()
                            ->title('Garantía confirmada por proveedor')
                            ->body('Reclamo ' . $folio . ' listo para devolver al cliente.')
                            ->sendToDatabase($u);
                    });
            } catch (\Throwable $e) { report($e); }
            // bitácora
            if (class_exists(\App\Models\Bitacora::class)) {
                \App\Models\Bitacora::registrar('proveedor confirmó garantía', 'Reclamo', $reclamo->id, ($reclamo->folio ?: '') . ' · fotos subidas');
            }
        }
        return view('erp.garantia.ok', ['link' => $link, 'reclamo' => $reclamo]);
    }

    public function etiquetas(string $token)
    {
        $link = LinkUnico::where('token', $token)->first();
        if (! $link) abort(404);
        $reclamo = Reclamo::find($link->reclamo_id);
        if (! $reclamo || ! $reclamo->pedido_id) abort(404);
        $pedido = \App\Models\PedidoEspecial::find($reclamo->pedido_id);
        if (! $pedido) abort(404);
        $path = \App\Services\Etiquetas::generarConBultos($pedido, (int) ($reclamo->bultos ?? 1), $reclamo->folio, $reclamo->producto);
        return response()->download($path, 'etiquetas-garantia-' . ($reclamo->folio ?: $reclamo->id) . '.pdf');
    }
}