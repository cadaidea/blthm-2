<?php
namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class CuentaController extends Controller
{
    public function panel(Request $request)
    {
        $id = $request->session()->get('cliente_id');
        if (! $id) {
            return redirect()->route('cuenta.login');
        }
        $cliente = Cliente::find($id);
        if (! $cliente) {
            $request->session()->forget('cliente_id');
            return redirect()->route('cuenta.login');
        }
        $pedidos = Pedido::where('cliente_id', $cliente->id)->latest()->get();

        $tieneRecibos = Schema::hasTable('recibos');
        $pagos = [];
        foreach ($pedidos as $p) {
            $pagado = $tieneRecibos ? (float) DB::table('recibos')->where('pedido_id', $p->id)->sum('monto') : 0.0;
            $recibos = $tieneRecibos ? DB::table('recibos')->where('pedido_id', $p->id)->orderBy('fecha')->orderBy('id')->get() : collect();
            $pagos[$p->id] = [
                'pagado'  => $pagado,
                'saldo'   => round((float) $p->total - $pagado, 2),
                'recibos' => $recibos,
            ];
        }

        return view('cuenta.panel', compact('cliente', 'pedidos', 'pagos'));
    }

    public function loginForm()
    {
        return view('cuenta.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required']);

        $rlKey = 'login:' . $request->ip() . ':' . $data['email'];
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($rlKey, 8)) {
            return back()->withErrors(['email' => 'Demasiados intentos. Intenta de nuevo en unos minutos.'])->onlyInput('email');
        }

        $cliente = Cliente::where('email', $data['email'])->whereNotNull('password')->first();
        if (! $cliente || ! Hash::check($data['password'], $cliente->password)) {
            \Illuminate\Support\Facades\RateLimiter::hit($rlKey, 300);
            return back()->withErrors(['email' => 'Correo o contraseña incorrectos.'])->onlyInput('email');
        }
        \Illuminate\Support\Facades\RateLimiter::clear($rlKey);
        $request->session()->put('cliente_id', $cliente->id);
        $request->session()->regenerate();
        return redirect()->route('cuenta.panel');
    }

    public function registroForm()
    {
        return view('cuenta.registro', ['turnstileActivo' => \App\Models\Ajuste::get('turnstile_activo') === '1', 'turnstileSiteKey' => \App\Models\Ajuste::get('turnstile_site_key')]);
    }

    public function registro(Request $request)
    {
        if ($request->filled('website')) {
            return redirect()->route('cuenta.panel');
        }

        $rlKey = 'registro:' . $request->ip();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($rlKey, 5)) {
            return back()->withErrors(['email' => 'Demasiados intentos. Intenta de nuevo en unos minutos.'])->onlyInput('email');
        }
        \Illuminate\Support\Facades\RateLimiter::hit($rlKey, 600);

        if (\App\Models\Ajuste::get('turnstile_activo') === '1') {
            $secret = \App\Models\Ajuste::get('turnstile_secret_key');
            $token = $request->input('cf-turnstile-response');
            if ($secret) {
                if (! $token) {
                    return back()->withErrors(['email' => 'Verificación de seguridad requerida.'])->onlyInput('email', 'nombre');
                }
                try {
                    $verify = \Illuminate\Support\Facades\Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                        'secret' => $secret,
                        'response' => $token,
                        'remoteip' => $request->ip(),
                    ])->json();
                    if (empty($verify['success'])) {
                        return back()->withErrors(['email' => 'No pudimos verificar que eres humano. Intenta de nuevo.'])->onlyInput('email', 'nombre');
                    }
                } catch (\Throwable $e) {
                    report($e);
                    return back()->withErrors(['email' => 'Error de verificación, intenta de nuevo.'])->onlyInput('email', 'nombre');
                }
            }
        }

        $data = $request->validate([
            'nombre'   => 'required|string|max:191',
            'email'    => 'required|email|max:191',
            'password' => 'required|string|min:6|confirmed',
        ]);
        $cliente = Cliente::firstOrNew(['email' => $data['email']]);
        if ($cliente->exists && $cliente->password) {
            return back()->withErrors(['email' => 'Ya existe una cuenta con este correo. Inicia sesión.'])->onlyInput('email');
        }
        $cliente->nombre = $data['nombre'];
        $cliente->password = Hash::make($data['password']);
        if (! $cliente->tipo_id) {
            $cliente->tipo_id = 'cedula';
        }
        $cliente->save();
        $request->session()->put('cliente_id', $cliente->id);
        $request->session()->regenerate();
        return redirect()->route('cuenta.panel');
    }

    public function salir(Request $request)
    {
        $request->session()->forget('cliente_id');
        $request->session()->regenerate();
        return redirect()->route('tienda.home');
    }

    public function verificar(\Illuminate\Http\Request $request, int $id, string $token)
    {
        $cliente = \App\Support\VerificacionCliente::verificar($id, $token);
        if (! $cliente) {
            return redirect()->route('cuenta.login')->withErrors(['email' => 'El enlace de verificación no es válido o ya fue usado.']);
        }
        // Si es el mismo que está logueado, refrescamos sesión; si no, igual queda verificado.
        return redirect()->route('cuenta.panel')->with('status', 'Correo verificado correctamente.');
    }

    public function reenviarVerificacion(\Illuminate\Http\Request $request)
    {
        $id = $request->session()->get('cliente_id');
        $cliente = $id ? \App\Models\Cliente::find($id) : null;
        if ($cliente && ! $cliente->email_verified_at) {
            \App\Support\VerificacionCliente::enviar($cliente);
        }
        return back()->with('status', 'Te enviamos un nuevo correo de verificación.');
    }
}
