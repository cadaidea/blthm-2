<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Stock;
use App\Services\PayPhone;
use App\Support\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    /** Paso 1: formulario de datos del cliente. */
    public function form()
    {
        if (! Cart::lineas()) {
            return redirect()->route('carrito.ver')->with('ok', 'Tu carrito está vacío.');
        }
        $lineas  = Cart::lineas();
        $totales = Cart::totales();
        return view('tienda.checkout', compact('lineas', 'totales'));
    }

    /** Paso 2: crear pedido pendiente y pasar a la página de pago. */
    public function crear(Request $request)
    {
        $lineas = Cart::lineas();
        if (! $lineas) {
            return redirect()->route('carrito.ver');
        }

        $data = $request->validate([
            'nombre'         => 'required|string|max:191',
            'identificacion' => 'required|string|max:20',
            'tipo_id'        => 'required|in:cedula,ruc,pasaporte',
            'email'          => 'required|email|max:191',
            'telefono'       => 'required|string|max:40',
            'direccion'      => 'nullable|string|max:191',
            'ciudad'         => 'nullable|string|max:100',
        ]);

        $montos = PayPhone::montos($lineas);
        $cuponCodigo = trim((string) $request->input("cupon"));

        $pedido = DB::transaction(function () use ($data, $lineas, $montos, $cuponCodigo) {
            $cliente = Cliente::updateOrCreate(
                ['identificacion' => $data['identificacion']],
                $data
            );
            $cup = \App\Services\Cupones::validar($cuponCodigo, $cliente, (float) $montos["total"]);
            if (! empty($cup["ok"])) { $montos = \App\Services\Cupones::aplicar($montos, $cup["descuento"]); }

            $pedido = new Pedido();
            $pedido->codigo       = 'BS-' . now()->format('ymd') . '-' . strtoupper(Str::random(4));
            $pedido->cliente_id   = $cliente->id;
            $pedido->estado       = 'pendiente_pago';
            $pedido->subtotal     = $montos['subtotal'];
            $pedido->iva          = $montos['iva'];
            $pedido->total        = $montos['total'];
            if (! empty($cup["ok"])) { $pedido->cupon_id = $cup["cupon"]->id; $pedido->cupon_codigo = $cup["cupon"]->codigo; $pedido->descuento = $cup["descuento"]; }
            $pedido->email        = $data['email'];
            $pedido->pp_client_tx = strtoupper(Str::random(12)); // único por transacción
            $pedido->save();

            foreach ($lineas as $l) {
                $p = $l['producto']; $c = $l['cantidad'];
                $neto = round($l['pvp'] / (1 + $l['iva_rate'] / 100), 2); // neto unitario
                PedidoItem::create([
                    'pedido_id'   => $pedido->id,
                    'producto_id' => $p->id,
                    'nombre'      => $p->nombre,
                    'variantes'   => $l['label'] ?: null,
                    'precio'      => $neto,
                    'iva_rate'    => $l['iva_rate'],
                    'cantidad'    => $c,
                    'subtotal'    => round($neto * $c, 2),
                ]);
            }
            return $pedido;
        });

        return redirect()->route('checkout.pagar', $pedido->codigo);
    }

    /** Paso 3: render de la Cajita de Pagos. */
    public function pagar(Pedido $pedido)
    {
        abort_unless($pedido->estado === 'pendiente_pago', 404);
        $montos = PayPhone::montos(
            $pedido->items->map(fn ($i) => [
                'pvp' => round((float) $i->precio * (1 + (float) $i->iva_rate / 100), 2),
                'iva_rate' => (float) $i->iva_rate,
                'cantidad' => (int) $i->cantidad,
            ])->all()
        );
        if ((float) $pedido->descuento > 0) { $montos = \App\Services\Cupones::aplicar($montos, (float) $pedido->descuento); }
        return view('tienda.pagar', compact('pedido', 'montos'));
    }

    /** Paso 4: URL de respuesta de Payphone -> confirmar. */
    public function confirmar(Request $request)
    {
        $id       = (int) $request->query('id');
        $clientTx = (string) $request->query('clientTransactionId');

        $pedido = Pedido::where('pp_client_tx', $clientTx)->first();
        if (! $pedido) {
            abort(404);
        }
        if ($pedido->estado === 'pagado') {
            return redirect()->route('checkout.gracias', $pedido->codigo);
        }

        $resp = PayPhone::confirmar($id, $clientTx);

        if (PayPhone::aprobado($resp)) {
            DB::transaction(function () use ($pedido, $resp, $id) {
                $pedido->estado            = 'pagado';
                $pedido->pp_transaction_id = (string) $id;
                $pedido->pp_auth           = (string) ($resp['authorizationCode'] ?? $resp['transactionId'] ?? '');
                $pedido->save();
                $this->descontarStock($pedido);
                \App\Services\Cupones::registrarUso($pedido);
            });
            Cart::vaciar();
            $this->correoConfirmacion($pedido);
            return redirect()->route('checkout.gracias', $pedido->codigo);
        }

        $pedido->estado = 'rechazado';
        $pedido->save();
        return view('tienda.gracias', ['pedido' => $pedido, 'aprobado' => false]);
    }

    public function gracias(Pedido $pedido)
    {
        return view('tienda.gracias', ['pedido' => $pedido, 'aprobado' => $pedido->estado === 'pagado']);
    }

    /* ---------- internos ---------- */

    private function descontarStock(Pedido $pedido): void
    {
        $localWeb = (int) config('tienda.local_web', 1);
        foreach ($pedido->items as $it) {
            if (! $it->producto_id) {
                continue;
            }
            $stock = Stock::where('producto_id', $it->producto_id)->where('local_id', $localWeb)->first();
            if ($stock) {
                $stock->cantidad = max(0, $stock->cantidad - $it->cantidad);
                $stock->save();
            }
        }
    }

    private function correoConfirmacion(Pedido $pedido): void
    {
        try {
            $lineas = $pedido->items->map(fn ($i) => "- {$i->nombre} x{$i->cantidad}")->implode("\n");
            $cuerpo = "Gracias por tu compra en " . config('tienda.marca') . ".\n\n"
                . "Pedido: {$pedido->codigo}\n{$lineas}\n\nTotal: $" . number_format($pedido->total, 2) . " (IVA incluido)\n";
            Mail::raw($cuerpo, function ($m) use ($pedido) {
                $m->to($pedido->email)->subject('Tu pedido ' . $pedido->codigo . ' — ' . config('tienda.marca'));
            });
        } catch (\Throwable $e) {
            \Log::warning('Correo pedido: ' . $e->getMessage());
        }
    }
}
