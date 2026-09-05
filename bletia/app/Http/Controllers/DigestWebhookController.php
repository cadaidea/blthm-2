<?php

namespace App\Http\Controllers;

use App\Models\Bounce;
use App\Models\Suscriptor;
use Illuminate\Http\Request;

class DigestWebhookController extends Controller
{
    public function bounce(Request $request)
    {
        $secret = config('services.digest.webhook_secret', env('DIGEST_WEBHOOK_SECRET'));
        $dado = $request->query('secret') ?: $request->header('X-Digest-Secret');
        abort_unless($secret && hash_equals((string) $secret, (string) $dado), 403);

        $payload = $request->all();
        // Brevo: { event: 'hard_bounce'|'soft_bounce'|'spam'|'blocked'|'invalid_email'|'unsubscribed', email, reason }
        $event = strtolower((string) ($payload['event'] ?? $payload['type'] ?? ''));
        $email = strtolower((string) ($payload['email'] ?? ''));
        $reason = (string) ($payload['reason'] ?? ($payload['error'] ?? ''));
        if (! $email) {
            return response()->json(['ok' => false], 200);
        }

        $tipo = match (true) {
            str_contains($event, 'hard') || $event === 'invalid_email' || $event === 'blocked' => 'hard',
            str_contains($event, 'spam') || str_contains($event, 'complaint') => 'complaint',
            str_contains($event, 'soft') || str_contains($event, 'deferred') => 'soft',
            $event === 'unsubscribed' => 'complaint',
            default => 'hard',
        };

        $sus = Suscriptor::where('email', $email)->first();
        Bounce::create([
            'suscriptor_id' => $sus?->id, 'email' => $email, 'tipo' => $tipo,
            'reason' => mb_substr($reason, 0, 500), 'source' => 'brevo', 'created_at' => now(),
        ]);

        if ($sus) {
            if (in_array($tipo, ['hard', 'complaint'], true)) {
                $sus->update(['estado' => 'rebotado']);
            } elseif ($tipo === 'soft') {
                $soft = Bounce::where('email', $email)->where('tipo', 'soft')->where('created_at', '>=', now()->subDays(30))->count();
                if ($soft >= 3) {
                    $sus->update(['estado' => 'rebotado']);
                }
            }
        }
        return response()->json(['ok' => true], 200);
    }
}
