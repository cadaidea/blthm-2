<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GuardadoController extends Controller
{
    /** Toggle: guarda el producto o lo quita si ya estaba. */
    public function toggle(Request $request, Producto $producto)
    {
        $clienteId = $request->session()->get('cliente_id');
        if (! $clienteId) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'auth' => false], 401);
            }
            return redirect()->route('cuenta.login');
        }

        $existe = DB::table('guardados')
            ->where('cliente_id', $clienteId)
            ->where('producto_id', $producto->id)
            ->first();

        if ($existe) {
            DB::table('guardados')->where('id', $existe->id)->delete();
            $guardado = false;
        } else {
            DB::table('guardados')->insert([
                'cliente_id'  => $clienteId,
                'producto_id' => $producto->id,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
            $guardado = true;
        }

        $total = DB::table('guardados')->where('cliente_id', $clienteId)->count();
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'guardado' => $guardado, 'total' => $total]);
        }
        return back();
    }

    /** Listado de productos guardados del cliente. */
    public function index(Request $request)
    {
        $clienteId = $request->session()->get('cliente_id');
        if (! $clienteId) {
            return redirect()->route('cuenta.login');
        }

        $productos = Producto::whereIn('id', function ($q) use ($clienteId) {
            $q->select('producto_id')->from('guardados')->where('cliente_id', $clienteId);
        })->where('activo', true)->with('imagenes')->latest('id')->get();

        $categorias = \App\Models\Categoria::where('activo', true)->orderBy('orden')->get();

        return view('cuenta.guardados', compact('productos', 'categorias'));
    }
}
