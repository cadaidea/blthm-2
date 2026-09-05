<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\LinkUnico;
use Illuminate\Http\Request;

class CompraProveedorController extends Controller
{
    public function show(string $token)
    {
        $link = LinkUnico::where('token', $token)->first();
        if (! $link || $link->tipo !== 'proveedor_compra') return view('erp.confirmar.error', ['msg' => 'Enlace no válido.']);
        if ($link->usado) return view('erp.confirmar.error', ['msg' => 'Este enlace ya fue usado.']);
        if ($link->expira_en && now()->gt($link->expira_en)) return view('erp.confirmar.error', ['msg' => 'Este enlace expiró.']);
        $compra = Compra::with(['items', 'proveedor'])->find($link->compra_id);
        if (! $compra) return view('erp.confirmar.error', ['msg' => 'Orden no encontrada.']);
        return view('erp.compra.show', ['link' => $link, 'compra' => $compra]);
    }

    public function submit(Request $request, string $token)
    {
        $link = LinkUnico::where('token', $token)->first();
        if (! $link || $link->tipo !== 'proveedor_compra' || $link->usado) {
            return view('erp.confirmar.error', ['msg' => 'Enlace no válido o ya usado.']);
        }
        $data = $request->validate([
            'foto_1' => 'required|image|max:8192',
            'foto_2' => 'required|image|max:8192',
            'bultos' => 'nullable|array',
            'bultos.*' => 'nullable|integer|min:1',
        ]);
        $dir = 'compras/' . $link->id;
        $request->file('foto_1')->store($dir, 'public');
        $request->file('foto_2')->store($dir, 'public');
        $link->update(['usado' => true, 'intentos' => $link->intentos + 1]);

        $compra = Compra::find($link->compra_id);
        if ($compra) {
            // guardar bultos confirmados por ítem
            foreach ((array) $request->input('bultos', []) as $itemId => $bultos) {
                \App\Models\CompraItem::where('id', $itemId)->where('compra_id', $compra->id)->update(['bultos' => max(1, (int) $bultos)]);
            }
            $compra->update(['estado' => 'listo_envio']);
            try {
                foreach (\Illuminate\Support\Facades\DB::table('users')->whereIn('rol', ['admin', 'operaciones', 'contabilidad'])->where('activo', true)->get() as $u) {
                    \Filament\Notifications\Notification::make()->success()
                        ->title('Proveedor confirmó compra lista')
                        ->body('Orden ' . ($compra->folio ?: '#' . $compra->id) . ' lista para enviar. Genera el despacho.')
                        ->sendToDatabase($u);
                }
            } catch (\Throwable $e) { report($e); }
            if (class_exists(\App\Models\Bitacora::class)) {
                \App\Models\Bitacora::registrar('proveedor confirmó compra lista', 'Compra', $compra->id, $compra->folio ?: '');
            }
        }
        return view('erp.compra.ok', ['link' => $link, 'compra' => $compra]);
    }

    public function etiquetas(string $token)
    {
        $link = LinkUnico::where('token', $token)->first();
        if (! $link) abort(404);
        $compra = Compra::with('items')->find($link->compra_id);
        if (! $compra) abort(404);
        $path = \App\Services\Etiquetas::generarParaCompra($compra);
        return response()->download($path, 'etiquetas-' . ($compra->folio ?: $compra->id) . '.pdf');
    }
}
