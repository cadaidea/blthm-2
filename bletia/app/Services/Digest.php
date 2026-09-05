<?php

namespace App\Services;

use App\Models\Ajuste;
use App\Models\Campania;
use App\Models\CampaniaEmail;
use App\Models\Suscriptor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class Digest
{
    /* ====== Cola ====== */

    /** Encola la campaña: una fila por suscriptor confirmado de las listas destino. */
    public static function encolar(Campania $c): int
    {
        $listaIds = collect($c->lista_ids ?? [])->map(fn ($x) => (int) $x)->filter()->all();
        if (! $listaIds) {
            return 0;
        }
        $subs = DB::table('lista_suscriptor')
            ->join('suscriptores', 'suscriptores.id', '=', 'lista_suscriptor.suscriptor_id')
            ->whereIn('lista_suscriptor.lista_id', $listaIds)
            ->where('suscriptores.estado', 'confirmado')
            ->distinct()
            ->pluck('suscriptores.id');

        foreach ($subs as $sid) {
            CampaniaEmail::firstOrCreate(
                ['campania_id' => $c->id, 'suscriptor_id' => $sid],
                ['estado' => 'cola', 'tracking_token' => Str::random(64)]
            );
        }
        $c->update(['total_destinatarios' => $subs->count(), 'estado' => 'enviando']);
        return $subs->count();
    }

    /* ====== Envío (lote con throttle) — llamado por cron ====== */

    public static function procesarLote(): array
    {
        $batch = (int) (Ajuste::get('digest_batch_size') ?: 20);
        $porHora = (int) (Ajuste::get('digest_speed_per_hour') ?: 240);

        $enviadosUltimaHora = CampaniaEmail::where('enviado_at', '>=', now()->subHour())->count();
        if ($enviadosUltimaHora >= $porHora) {
            return ['enviados' => 0, 'motivo' => 'throttle'];
        }
        $batch = min($batch, $porHora - $enviadosUltimaHora);

        // Campañas programadas cuyo momento llegó
        Campania::where('estado', 'programada')->where('programada_at', '<=', now())->get()
            ->each(fn ($c) => self::encolar($c));

        $pendientes = CampaniaEmail::with(['campania', 'suscriptor'])
            ->where('estado', 'cola')
            ->whereHas('campania', fn ($q) => $q->where('estado', 'enviando'))
            ->limit($batch)->get();

        $n = 0;
        foreach ($pendientes as $ce) {
            $ok = self::enviarUno($ce);
            if ($ok) $n++;
        }

        // Cerrar campañas sin pendientes
        Campania::where('estado', 'enviando')->get()->each(function (Campania $c) {
            if (! $c->emails()->where('estado', 'cola')->exists()) {
                $c->update(['estado' => 'enviada', 'enviada_at' => $c->enviada_at ?: now()]);
            }
        });

        return ['enviados' => $n];
    }

    public static function enviarUno(CampaniaEmail $ce): bool
    {
        $s = $ce->suscriptor; $c = $ce->campania;
        if (! $s || $s->estado !== 'confirmado') {
            $ce->update(['estado' => 'fallido', 'error' => 'suscriptor no confirmado']);
            return false;
        }
        $html = self::html($c, $s, $ce->tracking_token);
        $asunto = self::personalizar($c->asunto, $s);
        try {
            Mail::html($html, function ($m) use ($s, $asunto) {
                $m->to($s->email)->subject($asunto);
            });
            $ce->update(['estado' => 'enviado', 'enviado_at' => now(), 'intentos' => $ce->intentos + 1]);
            $c->increment('total_enviados');
            return true;
        } catch (\Throwable $e) {
            report($e);
            $ce->update([
                'estado' => $ce->intentos >= 2 ? 'fallido' : 'cola',
                'intentos' => $ce->intentos + 1,
                'error' => Str::limit($e->getMessage(), 240),
            ]);
            return false;
        }
    }

    /** Envío de prueba (sin tracking real). */
    public static function enviarPrueba(Campania $c, string $email): void
    {
        $s = new Suscriptor(['email' => $email, 'nombre' => 'Prueba', 'token' => 'test']);
        $s->id = 0;
        $html = self::html($c, $s, 'test');
        Mail::html($html, fn ($m) => $m->to($email)->subject('[PRUEBA] ' . self::personalizar($c->asunto, $s)));
    }

    /* ====== HTML ====== */

    public static function personalizar(string $texto, Suscriptor $s): string
    {
        $vars = [
            '{first_name}' => $s->nombre ?: '', '{last_name}' => $s->apellido ?: '',
            '{full_name}' => trim(($s->nombre ?? '') . ' ' . ($s->apellido ?? '')),
            '{email}' => $s->email, '{site_name}' => config('tienda.marca', config('app.name')),
            '{site_url}' => url('/'), '{current_year}' => date('Y'),
            '{cupon}' => optional(\App\Models\Cupon::where('activo', true)->latest('id')->first())->codigo ?: '',
        ];
        foreach ($vars as $k => $v) {
            $texto = str_replace([$k, '{' . $k . '}'], $v, $texto);
            $texto = str_replace('{' . trim($k, '{}') . '}', $v, $texto);
        }
        return strtr($texto, $vars);
    }

    public static function html(Campania $c, Suscriptor $s, string $token): string
    {
        $body = self::personalizar($c->cuerpo_html, $s);
        $body = self::inyectarTracking($body, $token);
        return self::wrap($body, $s, $c->preheader ? self::personalizar($c->preheader, $s) : null);
    }

    protected static function inyectarTracking(string $html, string $token): string
    {
        if ($token === 'test') {
            return $html;
        }
        $base = url('/digest/track/click') . '?t=' . $token . '&u=';
        $html = preg_replace_callback('/<a\s+([^>]*?)href=(["\'])([^"\']+)\2/i', function ($m) use ($base) {
            $u = $m[3];
            if (! preg_match('#^https?://#i', $u)) return $m[0];
            if (str_contains($u, '/digest/')) return $m[0];
            return '<a ' . $m[1] . 'href="' . $base . rawurlencode($u) . '"';
        }, $html);
        $pixel = '<img src="' . url('/digest/track/open') . '?t=' . $token . '" width="1" height="1" alt="" style="display:none">';
        return $html . $pixel;
    }

    public static function wrap(string $body, Suscriptor $s, ?string $preheader = null): string
    {
        $marca = config('tienda.marca', config('app.name'));
        $prefs = url('/digest/preferences?sid=' . $s->id . '&token=' . $s->token);
        $baja = url('/digest/unsubscribe?sid=' . $s->id . '&token=' . $s->token);
        $pre = $preheader ? '<span style="display:none;max-height:0;overflow:hidden">' . e($preheader) . '</span>' : '';
        return '<!doctype html><html><body style="margin:0;background:#f4f4f4;font-family:Arial,Helvetica,sans-serif;color:#161921">' . $pre .
            '<table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:24px 12px">' .
            '<table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:12px;overflow:hidden">' .
            '<tr><td style="background:#161921;padding:20px 28px;color:#ffffff;font-size:17px;letter-spacing:.2em">' . strtoupper(e($marca)) . '</td></tr>' .
            '<tr><td style="padding:28px;font-size:15px;line-height:1.65;color:#333333">' . $body . '</td></tr>' .
            '<tr><td style="background:#EFEBDD;padding:20px 28px;font-size:12px;color:#6b7280;text-align:center;line-height:1.7">' .
            'Recibes este correo porque te suscribiste con ' . e($s->email) . '.<br>' .
            '<a href="' . $prefs . '" style="color:#161921">Gestionar preferencias</a> · <a href="' . $baja . '" style="color:#161921">Darme de baja</a><br>' .
            '© ' . date('Y') . ' ' . e($marca) .
            '</td></tr></table></td></tr></table></body></html>';
    }
}
