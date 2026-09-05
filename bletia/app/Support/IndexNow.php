<?php

namespace App\Support;

use App\Models\Ajuste;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IndexNow
{
    /** Devuelve la clave IndexNow (autogenera y persiste si no existe). */
    public static function key(): string
    {
        $key = Ajuste::get('indexnow_key');
        if (empty($key)) {
            $key = bin2hex(random_bytes(16)); // 32 hex
            Ajuste::set('indexnow_key', $key);
        }
        return $key;
    }

    /**
     * Notifica una URL a IndexNow (Bing, Yandex, Seznam, Naver…).
     * Silencioso: nunca rompe el guardado si la red falla.
     */
    public static function ping(string $url): void
    {
        try {
            $key  = static::key();
            $host = parse_url(config('app.url'), PHP_URL_HOST) ?: 'www.bletia.ec';

            Http::timeout(4)->get('https://api.indexnow.org/indexnow', [
                'url'          => $url,
                'key'          => $key,
                'keyLocation'  => rtrim(config('app.url'), '/') . '/' . $key . '.txt',
            ]);
        } catch (\Throwable $e) {
            Log::warning('IndexNow ping falló: ' . $e->getMessage());
        }
    }
}
