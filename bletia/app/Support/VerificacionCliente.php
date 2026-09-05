<?php

namespace App\Support;

use App\Mail\DocumentoPedido;
use App\Models\Cliente;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class VerificacionCliente
{
    /**
     * Genera token, lo guarda y manda el correo de verificación.
     * Suave: nunca lanza excepción hacia arriba (no rompe el registro/compra).
     */
    public static function enviar(Cliente $cliente): bool
    {
        if (empty($cliente->email)) return false;
        if ($cliente->email_verified_at) return false; // ya verificado

        $token = Str::random(48);
        $cliente->verify_token = $token;
        $cliente->saveQuietly();

        $url = route('cuenta.verificar', ['id' => $cliente->id, 'token' => $token]);

        $cuerpo = '<p>Hola ' . e($cliente->nombre) . ',</p>'
            . '<p>Confirma tu correo para asegurar tu cuenta y recibir las actualizaciones de tus pedidos. Solo toma un clic.</p>'
            . '<p style="color:#8a949e;font-size:13px">Si no creaste esta cuenta, ignora este mensaje.</p>';

        $html = CorreoBrand::wrap('Confirma tu correo', $cuerpo, [
            'preheader' => 'Confirma tu correo en un clic',
            'cta'       => ['text' => 'Confirmar mi correo', 'url' => $url],
        ]);

        try {
            Mail::to($cliente->email)->send(new DocumentoPedido('Confirma tu correo', $html, []));
            return true;
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }

    /** Verifica el token. Devuelve el cliente si es válido, o null. */
    public static function verificar(int $id, string $token): ?Cliente
    {
        $cliente = Cliente::find($id);
        if (! $cliente || empty($cliente->verify_token)) return null;
        if (! hash_equals($cliente->verify_token, $token)) return null;

        $cliente->email_verified_at = now();
        $cliente->verify_token = null;
        $cliente->saveQuietly();

        return $cliente;
    }
}
