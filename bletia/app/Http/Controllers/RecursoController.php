<?php

namespace App\Http\Controllers;

use App\Models\AvisoStock;
use App\Models\Lista;
use App\Models\Recurso;
use App\Models\RecursoToken;
use App\Models\Suscriptor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RecursoController extends Controller
{
    /** POST /api/digest/recurso — email a cambio del recurso. Suscribe + envía enlace. */
    public function solicitar(Request $r)
    {
        $data = $r->validate(['slug' => 'required|string', 'email' => 'required|email', 'nombre' => 'nullable|string']);
        $rec = Recurso::where('slug', $data['slug'])->where('activo', true)->firstOrFail();
        $codigoCupon = $rec->cupon_id ? optional(\App\Models\Cupon::find($rec->cupon_id))->codigo : $rec->cupon_codigo;

        $sub = Suscriptor::firstOrCreate(
            ['email' => mb_strtolower($data['email'])],
            ['nombre' => $data['nombre'] ?? null, 'estado' => 'confirmado', 'token' => Str::random(64),
             'source' => 'resource:' . $rec->id, 'confirmed_at' => now()]
        );
        foreach ((array) $rec->lista_ids as $lid) {
            \Illuminate\Support\Facades\DB::table('lista_suscriptor')->updateOrInsert(
                ['lista_id' => $lid, 'suscriptor_id' => $sub->id], ['created_at' => now()]
            );
        }

        $tok = RecursoToken::create([
            'recurso_id' => $rec->id, 'suscriptor_id' => $sub->id, 'email' => $sub->email,
            'token' => Str::random(64), 'expira_at' => now()->addDays(7),
        ]);

        $url = url('/recurso/descargar/' . $tok->token);
        $cuerpo = '<p>Aquí tienes <strong>' . e($rec->nombre) . '</strong>.</p>'
            . ($rec->tipo === 'cupon'
                ? '<p>Tu código: <strong>' . e($codigoCupon) . '</strong></p>'
                : '<p><a href="' . $url . '">Descargar</a> (enlace válido 7 días).</p>');
        $html = \App\Support\CorreoBrand::wrap($rec->nombre, $cuerpo, ['cta' => ['text' => 'Obtener', 'url' => $url]]);
        try { \Illuminate\Support\Facades\Mail::html($html, fn ($m) => $m->to($sub->email)->subject($rec->nombre)); } catch (\Throwable $e) { report($e); }

        return response()->json(['ok' => true]);
    }

    /** GET /recurso/descargar/{token} */
    public function descargar(string $token)
    {
        $tok = RecursoToken::where('token', $token)->firstOrFail();
        abort_if($tok->expira_at && $tok->expira_at->isPast(), 410, 'Enlace expirado');
        $rec = $tok->recurso;
        $tok->update(['usado_at' => now()]);
        $rec->increment('descargas');

        if ($rec->tipo === 'cupon') {
            return response('Tu código: ' . $codigoCupon, 200)->header('Content-Type', 'text/plain');
        }
        abort_if(! $rec->archivo || ! Storage::disk('public')->exists($rec->archivo), 404);
        return Storage::disk('public')->download($rec->archivo, $rec->nombre);
    }

    /** POST /api/digest/back-in-stock */
    public function avisoStock(Request $r)
    {
        $data = $r->validate(['producto_id' => 'required|integer', 'email' => 'required|email']);
        AvisoStock::firstOrCreate(
            ['producto_id' => $data['producto_id'], 'email' => mb_strtolower($data['email'])],
            ['notificado' => false]
        );
        return response()->json(['ok' => true]);
    }
}
