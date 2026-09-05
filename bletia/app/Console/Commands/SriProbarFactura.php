<?php

namespace App\Console\Commands;

use App\Models\SriComprobante;
use App\Services\Sri\EmisorFactura;
use Illuminate\Console\Command;

class SriProbarFactura extends Command
{
    protected $signature = 'sri:probar-factura';
    protected $description = 'Emite una factura de prueba contra el SRI y muestra la respuesta paso a paso.';

    public function handle(): int
    {
        $this->info('Emitiendo factura de prueba...');
        $venta = [
            'pedido_id' => null, 'cliente_id' => null,
            'comprador' => [
                'tipo_id' => '07', // consumidor final
                'identificacion' => '9999999999999',
                'razon' => 'CONSUMIDOR FINAL',
                'direccion' => 'Cuenca',
                'email' => null, 'telefono' => null,
            ],
            'items' => [
                ['codigo' => 'PRUEBA1', 'descripcion' => 'Producto de prueba', 'cantidad' => 1, 'precio_unitario' => 10.00, 'descuento' => 0, 'iva_rate' => 15],
            ],
            'forma_pago' => '01', 'origen' => 'Prueba',
        ];

        $r = EmisorFactura::emitir($venta);
        $this->line('');
        $this->line('Resultado: ' . ($r['ok'] ? '<fg=green>AUTORIZADO</>' : '<fg=red>NO COMPLETADO</>'));
        $this->line('Mensaje: ' . ($r['msg'] ?? '—'));
        if (! empty($r['comprobante_id'])) {
            $c = SriComprobante::find($r['comprobante_id']);
            $this->line('Comprobante #' . $c->id . ' · estado: ' . $c->estado . ' · clave: ' . $c->clave_acceso);
            $this->line('');
            $this->line('--- Logs ---');
            foreach (\Illuminate\Support\Facades\DB::table('sri_logs')->where('comprobante_id', $c->id)->get() as $l) {
                $this->line(sprintf('[%s] %s: %s', $l->paso, $l->resultado, $l->mensaje));
            }
        }
        return self::SUCCESS;
    }
}
