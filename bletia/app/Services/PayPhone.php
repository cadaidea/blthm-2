<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PayPhone
{
    /**
     * Calcula los montos en CENTAVOS que exige Payphone.
     * $lineas: array de ['producto' => Producto, 'cantidad' => int].
     * Regla: amount = amountWithoutTax + amountWithTax + tax.
     */
    public static function montos(array $lineas): array
    {
        $baseGravada = 0.0; $exento = 0.0; $impuesto = 0.0;
        foreach ($lineas as $l) {
            $c = (int) ($l['cantidad'] ?? 1);
            if (isset($l['pvp'])) {                       // PVP unitario (IVA incl.)
                $iva = (float) ($l['iva_rate'] ?? 0);
                $neto = ((float) $l['pvp']) / (1 + $iva / 100) * $c;
            } else {                                       // compatibilidad: producto neto
                $p = $l['producto']; $iva = (float) $p->iva_rate; $neto = (float) $p->precio * $c;
            }
            if ($iva > 0) { $baseGravada += $neto; $impuesto += $neto * $iva / 100; }
            else { $exento += $neto; }
        }
        $amountWithTax = (int) round($baseGravada * 100);
        $amountWithoutTax = (int) round($exento * 100);
        $tax = (int) round($impuesto * 100);
        $amount = $amountWithoutTax + $amountWithTax + $tax;
        return [
            'amount' => $amount, 'amountWithTax' => $amountWithTax,
            'amountWithoutTax' => $amountWithoutTax, 'tax' => $tax,
            'subtotal' => round($baseGravada + $exento, 2),
            'iva' => round($impuesto, 2), 'total' => round($amount / 100, 2),
        ];
    }

    /**
     * Confirma la transacción contra Payphone (fase 2 obligatoria).
     * Devuelve el array de respuesta o null si falla la llamada.
     */
    public static function confirmar(int $id, string $clientTx): ?array
    {
        try {
            $resp = Http::withToken(config('payphone.token'))
                ->acceptJson()
                ->timeout(20)
                ->post(config('payphone.confirm_url'), [
                    'id'                  => $id,
                    'clientTransactionId' => $clientTx,
                ]);

            return $resp->json();
        } catch (\Throwable $e) {
            \Log::error('PayPhone confirmar: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Prueba las credenciales contra la API de Payphone sin cobrar nada.
     * Llama al endpoint de confirmación con un ID inventado: si el token/Store ID
     * son inválidos, Payphone responde 401/403. Si son válidos, responde 400/404
     * con un cuerpo JSON estructurado (transacción no encontrada), lo cual confirma
     * que las credenciales fueron aceptadas.
     */
    public static function probarCredenciales(string $storeId, string $token): array
    {
        try {
            $resp = Http::withToken($token)
                ->acceptJson()
                ->timeout(15)
                ->post(config('payphone.confirm_url'), [
                    'id' => 999999999,
                    'clientTransactionId' => 'TEST-' . uniqid(),
                ]);

            $status = $resp->status();

            if (in_array($status, [401, 403], true)) {
                return ['ok' => false, 'msg' => 'Token o Store ID inválidos (Payphone rechazó la autenticación).'];
            }

            // Cualquier otra respuesta (400, 404, 200) con JSON estructurado confirma
            // que el token fue aceptado; solo la transacción de prueba no existe.
            $json = $resp->json();
            if (is_array($json)) {
                return ['ok' => true, 'msg' => 'Credenciales válidas. Payphone respondió correctamente (HTTP ' . $status . ').'];
            }

            return ['ok' => false, 'msg' => 'Respuesta inesperada de Payphone (HTTP ' . $status . ').'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'msg' => 'No se pudo contactar a Payphone: ' . $e->getMessage()];
        }
    }

    /** ¿La respuesta de confirmación indica pago aprobado? */
    public static function aprobado(?array $resp): bool
    {
        if (! $resp) {
            return false;
        }
        $estado = $resp['transactionStatus'] ?? '';
        $code   = $resp['statusCode'] ?? null;
        return $estado === 'Approved' || (int) $code === 3;
    }
}
