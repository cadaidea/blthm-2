<?php

namespace App\Http\Controllers;

use App\Models\CampaniaClic;
use App\Models\CampaniaEmail;
use Illuminate\Http\Request;

class DigestTrackController extends Controller
{
    public function open(Request $request)
    {
        $ce = CampaniaEmail::where('tracking_token', (string) $request->query('t'))->first();
        if ($ce && ! $ce->abierto_at) {
            $ce->update(['abierto_at' => now(), 'estado' => $ce->estado === 'enviado' ? 'abierto' : $ce->estado]);
            $ce->campania?->increment('total_aperturas');
        }
        $gif = base64_decode('R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
        return response($gif, 200)->header('Content-Type', 'image/gif')->header('Cache-Control', 'no-store');
    }

    public function click(Request $request)
    {
        $url = (string) $request->query('u', '');
        if (! filter_var($url, FILTER_VALIDATE_URL) || ! preg_match('#^https?://#i', $url)) {
            return redirect(url('/'));
        }
        $ce = CampaniaEmail::where('tracking_token', (string) $request->query('t'))->first();
        if ($ce) {
            CampaniaClic::create(['campania_email_id' => $ce->id, 'url' => mb_substr($url, 0, 2048), 'created_at' => now()]);
            $ce->increment('clics');
            if (! $ce->abierto_at) {
                $ce->update(['abierto_at' => now()]);
                $ce->campania?->increment('total_aperturas');
            }
            if ($ce->estado !== 'clicado') {
                $ce->update(['estado' => 'clicado']);
                $ce->campania?->increment('total_clics');
            }
        }
        return redirect()->away($url, 302);
    }
}
