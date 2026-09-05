<?php

namespace App\Http\Controllers;

use App\Services\EstadoPedidoErp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeguimientoController extends Controller
{
    public function show(Request $request)
    {
        $ref = $request->query('p') ?: $request->query('numero');
        if (! $ref) {
            return view('erp.seguimiento.form');
        }
        $pedido = DB::table('pedidos')->where('id', $ref)->first();
        if (! $pedido && Schema::hasColumn('pedidos', 'folio')) {
            $pedido = DB::table('pedidos')->where('folio', $ref)->first();
        }
        if (! $pedido && Schema::hasColumn('pedidos', 'numero')) {
            $pedido = DB::table('pedidos')->where('numero', $ref)->first();
        }
        if (! $pedido) {
            return view('erp.seguimiento.form', ['error' => 'No encontramos ese pedido.']);
        }

        $labels = EstadoPedidoErp::ESTADOS_CLIENTE + [
            'pagado' => 'Pagado', 'pendiente' => 'En revisión', 'en_proceso' => 'En proceso',
        ];
        $historial = Schema::hasTable('historial_pedido')
            ? DB::table('historial_pedido')->where('pedido_id', $pedido->id)->orderBy('creado_en')->get()
            : collect();

        $actual = $pedido->estado_erp ?? null;
        return view('erp.seguimiento.show', [
            'numero'    => $pedido->folio ?? $pedido->numero ?? $pedido->id,
            'actual'    => $actual,
            'actualLbl' => $labels[$actual] ?? ($actual ? ucfirst(str_replace('_', ' ', $actual)) : 'En proceso'),
            'historial' => $historial,
            'labels'    => $labels,
        ]);
    }
}
