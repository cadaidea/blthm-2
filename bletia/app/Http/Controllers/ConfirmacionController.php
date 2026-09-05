<?php

namespace App\Http\Controllers;

use App\Models\Confirmacion;
use App\Models\LinkUnico;
use App\Services\DespachoErp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ConfirmacionController extends Controller
{
    protected array $labels = [
        'cliente_retiro' => ['titulo' => 'Confirmar retiro', 'boton' => 'Recibí conforme', 'receptor' => true],
        'transportista'  => ['titulo' => 'Confirmar entrega', 'boton' => 'Entregado', 'receptor' => true],
        'proveedor'      => ['titulo' => 'Confirmar pedido listo', 'boton' => 'Pedido listo', 'receptor' => false],
    ];

    public function show(string $token)
    {
        $link = LinkUnico::where('token', $token)->first();
        if (! $link) return view('erp.confirmar.error', ['msg' => 'Enlace no válido.']);
        if (! $link->vigente()) {
            return view('erp.confirmar.error', ['msg' => $link->usado ? 'Este enlace ya fue usado.' : 'Este enlace expiró.']);
        }
        $cfg = $this->labels[$link->tipo] ?? $this->labels['proveedor'];
        $itemsBultos = [];
        if ($link->tipo === 'proveedor' && $link->pedido_id) {
            $itemsBultos = \Illuminate\Support\Facades\DB::table('pedido_items')->where('pedido_id', $link->pedido_id)->get()
                ->map(fn ($it) => ['id' => $it->id, 'nombre' => $it->nombre, 'bultos' => max(1, (int) ($it->bultos ?? 1))])->all();
        }
        return view('erp.confirmar.show', ['link' => $link, 'cfg' => $cfg, 'itemsBultos' => $itemsBultos]);
    }

    public function submit(Request $request, string $token)
    {
        $link = LinkUnico::where('token', $token)->first();
        if (! $link || ! $link->vigente()) {
            return view('erp.confirmar.error', ['msg' => 'Enlace no válido o ya usado.']);
        }
        $cfg = $this->labels[$link->tipo] ?? $this->labels['proveedor'];

        $reglas = ['foto_1' => 'required|image|max:8192', 'foto_2' => 'required|image|max:8192'];
        if (! empty($cfg['receptor'])) {
            $reglas['receptor_nombre'] = 'required|string|max:160';
            $reglas['receptor_celular'] = 'nullable|string|max:40';
            if ($link->tipo === 'cliente_retiro') $reglas['receptor_cedula'] = 'nullable|string|max:20';
        }
        $data = $request->validate($reglas);

        $dir = 'confirmaciones/' . $link->id;
        $f1 = $request->file('foto_1')->store($dir, 'public');
        $f2 = $request->file('foto_2')->store($dir, 'public');

        $conf = Confirmacion::create([
            'link_id'          => $link->id,
            'despacho_id'      => $link->despacho_id,
            'pedido_id'        => $link->pedido_id,
            'receptor_nombre'  => $data['receptor_nombre'] ?? null,
            'receptor_cedula'  => $data['receptor_cedula'] ?? null,
            'receptor_celular' => $data['receptor_celular'] ?? null,
            'foto_1_url'       => Storage::url($f1),
            'foto_2_url'       => Storage::url($f2),
            'ip_origen'        => $request->ip(),
            'confirmado_en'    => now(),
        ]);

        $link->update(['usado' => true, 'intentos' => $link->intentos + 1]);
        try { if ($link->pedido_id) { \App\Services\Traza::registrar(\App\Models\PedidoEspecial::find($link->pedido_id), 'recibido', 'Recibido por '.($request->input('receptor_nombre') ?? '')); } } catch (\Throwable $e) {}

        // guardar bultos confirmados por el proveedor
        if ($link->tipo === 'proveedor' && $request->has('bultos')) {
            foreach ((array) $request->input('bultos') as $bi) {
                if (! empty($bi['item_id'])) {
                    \Illuminate\Support\Facades\DB::table('pedido_items')->where('id', $bi['item_id'])->update(['bultos' => max(1, (int) ($bi['cantidad'] ?? 1))]);
                }
            }
        }
        try { DespachoErp::alConfirmar($link, $conf); } catch (\Throwable $e) { report($e); }

        return view('erp.confirmar.ok', ['cfg' => $cfg, 'link' => $link, 'esProveedor' => $link->tipo === 'proveedor']);
    }

    public function etiquetas(string $token)
    {
        $link = LinkUnico::where('token', $token)->first();
        if (! $link || ! $link->pedido_id) abort(404);
        $p = \App\Models\PedidoEspecial::find($link->pedido_id);
        if (! $p) abort(404);
        $path = \App\Services\Etiquetas::generar($p);
        return response()->download($path, 'etiquetas-' . ($p->folio ?: $p->id) . '.pdf');
    }
}
