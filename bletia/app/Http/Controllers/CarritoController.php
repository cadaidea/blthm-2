<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Support\Cart;
use Illuminate\Http\Request;

class CarritoController extends Controller
{
    public function ver()
    {
        $lineas = Cart::lineas();
        $totales = Cart::totales();
        return view('tienda.carrito', compact('lineas', 'totales'));
    }

    public function agregar(Request $request, Producto $producto)
    {
        abort_unless($producto->activo, 404);
        $cant = max(1, (int) $request->input('cantidad', 1));
        $varianteId = $request->input('variante_id');
        $varianteId = $varianteId !== null && $varianteId !== '' ? (int) $varianteId : null;
        Cart::agregar($producto->id, $varianteId, $cant);
        return redirect()->route('carrito.ver')->with('ok', 'Producto agregado al carrito.');
    }

    public function actualizar(Request $request)
    {
        foreach ((array) $request->input('cantidad', []) as $key => $cant) {
            Cart::actualizar((string) $key, (int) $cant);
        }
        return redirect()->route('carrito.ver')->with('ok', 'Carrito actualizado.');
    }

    public function eliminar(Request $request)
    {
        Cart::eliminar((string) $request->input('key'));
        return redirect()->route('carrito.ver')->with('ok', 'Producto eliminado.');
    }
}
