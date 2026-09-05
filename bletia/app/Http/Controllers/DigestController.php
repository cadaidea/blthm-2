<?php

namespace App\Http\Controllers;

use App\Mail\ConfirmarSuscripcion;
use App\Models\Formulario;
use App\Models\Lista;
use App\Models\Suscriptor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class DigestController extends Controller
{
    /** Lista por defecto (Newsletter). */
    protected function listaDefault(): Lista
    {
        return Lista::firstOrCreate(['slug' => 'newsletter'], ['nombre' => 'Newsletter', 'publica' => true]);
    }

    /** Respuesta: JSON si es AJAX, si no redirige con flash. */
    protected function resp(Request $request, string $msg, bool $ok = true)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => $ok, 'message' => $msg], $ok ? 200 : 429);
        }
        return $ok ? back()->with('ok', $msg) : back()->withErrors(['email' => $msg]);
    }

    public function subscribe(Request $request)
    {
        // Honeypot
        if (filled($request->input('website'))) {
            return $this->resp($request, 'Gracias por suscribirte.');
        }
        // Rate limit por IP
        $key = 'sub:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return $this->resp($request, 'Demasiados intentos, prueba más tarde.', false);
        }
        RateLimiter::hit($key, 3600);

        // Cloudflare Turnstile (anti-spam), configurable desde Ajustes -> Seguridad
        if (\App\Models\Ajuste::get('turnstile_activo') === '1') {
            $secretKey = \App\Models\Ajuste::get('turnstile_secret_key');
            $token = $request->input('cf-turnstile-response');
            if (! $secretKey || ! $token) {
                return $this->resp($request, 'Verificación de seguridad requerida.', false);
            }
            try {
                $verificacion = \Illuminate\Support\Facades\Http::asForm()->post(
                    'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                    ['secret' => $secretKey, 'response' => $token, 'remoteip' => $request->ip()]
                )->json();
                if (empty($verificacion['success'])) {
                    return $this->resp($request, 'No pudimos verificar que eres humano. Intenta de nuevo.', false);
                }
            } catch (\Throwable $e) {
                report($e);
                return $this->resp($request, 'Error de verificación, intenta de nuevo.', false);
            }
        }

        $data = $request->validate([
            'email'    => 'required|email:rfc',
            'nombre'   => 'nullable|string|max:120',
            'apellido' => 'nullable|string|max:120',
            'telefono' => 'nullable|string|max:40',
            'ciudad'   => 'nullable|string|max:120',
            'nacimiento' => 'nullable|date',
            'form_id'  => 'nullable|integer',
            'listas'   => 'nullable|array',
        ]);

        // Listas destino
        $listaIds = collect($data['listas'] ?? []);
        if ($formId = $request->input('form_id')) {
            $f = Formulario::find($formId);
            if ($f) {
                $permitidas = collect($f->lista_ids ?? []);
                $elige = data_get($f->opciones, 'elegir_lista');
                $pedidas = collect($data['listas'] ?? [])->map(fn ($x) => (int) $x);
                $inter = $pedidas->intersect($permitidas->map(fn ($x) => (int) $x));
                $listaIds = ($elige && $inter->isNotEmpty()) ? $inter : $permitidas;
                $f->increment('conversiones');
            }
        }
        if ($listaIds->isEmpty()) {
            $listaIds = collect([$this->listaDefault()->id]);
        }

        $s = Suscriptor::firstOrNew(['email' => strtolower($data['email'])]);
        $yaConfirmado = $s->exists && $s->estado === 'confirmado';

        if (! $s->exists) {
            $s->source = $request->input('form_id') ? 'form:' . $request->input('form_id') : 'newsletter';
        }
        $s->nombre = $data['nombre'] ?? $s->nombre;
        $s->apellido = $data['apellido'] ?? $s->apellido;
        if (\Illuminate\Support\Facades\Schema::hasColumn('suscriptores','telefono')) $s->telefono = $data['telefono'] ?? $s->telefono;
        if (\Illuminate\Support\Facades\Schema::hasColumn('suscriptores','ciudad')) $s->ciudad = $data['ciudad'] ?? $s->ciudad;
        $s->ip = $request->ip();
        if (\Illuminate\Support\Facades\Schema::hasColumn('suscriptores','nacimiento') && ! empty($data['nacimiento'])) $s->nacimiento = $data['nacimiento'];
        if (in_array($s->estado, ['baja', 'rebotado'], true) || ! $s->exists) {
            $s->estado = 'pendiente';
            $s->token = Str::random(64);
            $s->confirmed_at = null;
            $s->unsubscribed_at = null;
        }
        $s->save();
        $s->listas()->syncWithoutDetaching($listaIds->all());

        if ($yaConfirmado) {
            return $this->resp($request, 'Ya estás suscrito. ¡Gracias!');
        }

        // Doble opt-in
        try {
            Mail::to($s->email)->send(new ConfirmarSuscripcion($s));
        } catch (\Throwable $e) {
            report($e);
            return $this->resp($request, 'Te registramos. Si no llega el correo de confirmación, escríbenos.');
        }

        return $this->resp($request, 'Revisa tu correo y confirma tu suscripción.');
    }

    public function confirm(Request $request)
    {
        $s = Suscriptor::where('id', $request->query('sid'))->where('token', $request->query('token'))->first();
        abort_unless($s, 404);
        $estado = 'ok';
        if ($s->estado !== 'confirmado') {
            $s->update(['estado' => 'confirmado', 'confirmed_at' => now()]);
        } else {
            $estado = 'ya';
        }
        return view('digest.confirmado', ['estado' => $estado]);
    }

    public function unsubscribeForm(Request $request)
    {
        $s = Suscriptor::where('id', $request->query('sid'))->where('token', $request->query('token'))->first();
        abort_unless($s, 404);
        return view('digest.baja', ['s' => $s]);
    }

    public function unsubscribe(Request $request)
    {
        $s = Suscriptor::where('id', $request->input('sid'))->where('token', $request->input('token'))->first();
        abort_unless($s, 404);
        $s->update(['estado' => 'baja', 'unsubscribed_at' => now()]);
        $s->listas()->detach();
        return view('digest.baja', ['s' => $s, 'hecho' => true]);
    }

    public function preferencesForm(Request $request)
    {
        $s = Suscriptor::with('listas')->where('id', $request->query('sid'))->where('token', $request->query('token'))->first();
        abort_unless($s, 404);
        $listas = Lista::where('publica', true)->orderBy('nombre')->get();
        $actuales = $s->listas->pluck('id')->all();
        return view('digest.preferencias', compact('s', 'listas', 'actuales'));
    }

    public function preferences(Request $request)
    {
        $s = Suscriptor::where('id', $request->input('sid'))->where('token', $request->input('token'))->first();
        abort_unless($s, 404);
        $s->listas()->sync((array) $request->input('listas', []));
        return view('digest.preferencias', [
            's' => $s->load('listas'),
            'listas' => Lista::where('publica', true)->orderBy('nombre')->get(),
            'actuales' => $s->listas->pluck('id')->all(),
            'guardado' => true,
        ]);
    }

    public function impression(Request $request)
    {
        Formulario::where('id', (int) $request->query('f'))->increment('impresiones');
        return response()->noContent();
    }
}