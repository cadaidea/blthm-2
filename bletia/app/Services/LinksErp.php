<?php

namespace App\Services;

use App\Models\LinkUnico;
use Illuminate\Support\Str;

class LinksErp
{
    public static function crear(string $tipo, ?int $pedidoId = null, ?int $despachoId = null): LinkUnico
    {
        $horas = (int) (env('LINK_EXPIRES_HOURS', 72));
        return LinkUnico::create([
            'token'       => Str::random(80),
            'tipo'        => $tipo,
            'pedido_id'   => $pedidoId,
            'despacho_id' => $despachoId,
            'usado'       => false,
            'expira_en'   => now()->addHours($horas ?: 72),
        ]);
    }

    public static function url(LinkUnico $l): string
    {
        return url('/confirmar/' . $l->token);
    }
}
