<?php
namespace App\Http\Controllers;
use App\Models\Ajuste;
use App\Models\ContactMessage;
use App\Models\FormularioContacto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class ContactoController extends Controller
{
    public function index(string $slug = 'contacto')
    {
        $formulario = FormularioContacto::where('slug', $slug)->where('activo', true)->first();
        abort_unless($formulario, 404);

        $pagina = \App\Models\Pagina::where("slug", $slug)->where("activo", true)->first();
        $tieneBloqueFormulario = $pagina && collect($pagina->bloques ?? [])->contains(
            fn ($b) => ($b["type"] ?? null) === "formulario_contacto"
                && ($b["data"]["formulario_slug"] ?? null) === $slug
        );
        if ($tieneBloqueFormulario) {
            return view("tienda.contacto-page", ["pagina" => $pagina]);
        }

        return view('tienda.contacto', [
            'formulario' => $formulario,
            'turnstileActivo' => Ajuste::get('turnstile_activo') === '1',
            'turnstileSiteKey' => Ajuste::get('turnstile_site_key'),
            'temas' => $formulario->temasArray(),
        ]);
    }

    public function submit(Request $request, string $slug = 'contacto')
    {
        $formulario = FormularioContacto::where('slug', $slug)->where('activo', true)->first();
        abort_unless($formulario, 404);

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190',
            'subject' => 'nullable|string|max:190',
            'message' => 'required|string|max:5000',
            'website' => 'nullable|max:0',
        ], [
            'website.max' => 'Solicitud inválida.',
        ]);

        if (Ajuste::get('turnstile_activo') === '1') {
            $secret = Ajuste::get('turnstile_secret_key');
            $token = $request->input('cf-turnstile-response');
            if ($secret) {
                if (! $token) {
                    return back()->withInput()->with('contact_error', 'Verificación de seguridad requerida.');
                }
                try {
                    $verify = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                        'secret' => $secret,
                        'response' => $token,
                        'remoteip' => $request->ip(),
                    ])->json();
                    if (empty($verify['success'])) {
                        return back()->withInput()->with('contact_error', 'No pudimos verificar que eres humano. Intenta de nuevo.');
                    }
                } catch (\Throwable $e) {
                    report($e);
                    return back()->withInput()->with('contact_error', 'Error de verificación, intenta de nuevo.');
                }
            }
        }

        $mensaje = ContactMessage::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'subject' => ($formulario->nombre !== 'Contacto general' ? '[' . $formulario->nombre . '] ' : '') . ($data['subject'] ?? ''),
            'message' => $data['message'],
            'ip' => $request->ip(),
        ]);

        $to = $formulario->correo_destino ?: Ajuste::get('smtp_from_address');
        if ($to) {
            try {
                Mail::send('emails.contacto', ['msg' => $mensaje, 'formulario' => $formulario], function ($mail) use ($to, $mensaje) {
                    $mail->to($to)->subject('Nuevo mensaje: ' . ($mensaje->subject ?: $mensaje->name));
                    $mail->replyTo($mensaje->email, $mensaje->name);
                });
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return redirect()->route('contacto.form', $slug)
            ->with('contact_success', $formulario->mensaje_exito ?: '¡Gracias! Tu mensaje fue enviado, te responderemos pronto.');
    }
}
