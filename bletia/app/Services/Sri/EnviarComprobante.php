<?php

namespace App\Services\Sri;

use App\Mail\ComprobanteSriMail;
use App\Models\SriComprobante;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/** Genera RIDE y envía el comprobante por correo (PDF + XML). */
class EnviarComprobante
{
    public static function procesar(SriComprobante $c, bool $enviarCorreo = true): array
    {
        try {
            if (! $c->pdf_path || ! is_file($c->pdf_path)) {
                Ride::generar($c->fresh());
                $c->refresh();
            }
        } catch (\Throwable $e) {
            Log::error('RIDE error: ' . $e->getMessage());
            return ['ok' => false, 'msg' => 'Error generando PDF: ' . $e->getMessage()];
        }

        if (! $enviarCorreo) return ['ok' => true, 'msg' => 'RIDE generado'];

        $email = $c->receptor_email;
        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => true, 'msg' => 'RIDE generado (sin correo: receptor sin email)'];
        }
        try {
            Mail::to($email)->send(new ComprobanteSriMail($c));
            return ['ok' => true, 'msg' => 'RIDE generado y enviado a ' . $email];
        } catch (\Throwable $e) {
            Log::error('Correo SRI error: ' . $e->getMessage());
            return ['ok' => false, 'msg' => 'PDF ok pero falló el correo: ' . $e->getMessage()];
        }
    }
}
