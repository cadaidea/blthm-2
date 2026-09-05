<?php
namespace App\Http\Controllers;

use App\Models\Pagina;

class PaginaController extends Controller
{
    public function show(Pagina $pagina)
    {
        abort_unless($pagina->activo, 404);
        return view('paginas.show', compact('pagina') + ['headerTipo' => 'paginas']);
    }
}
