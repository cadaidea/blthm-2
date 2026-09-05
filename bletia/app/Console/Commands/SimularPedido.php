<?php

namespace App\Console\Commands;

use App\Models\Despacho;
use App\Models\PedidoEspecial;
use App\Models\Transportista;
use App\Services\DespachoErp;
use App\Services\EstadoPedidoErp;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SimularPedido extends Command
{
    protected $signature = 'erp:simular {correo} {--solo-test : Solo envía un correo de prueba}';
    protected $description = 'Simula una venta por pedido (transportista) de punta a punta';

    public function handle(): int
    {
        $correo = $this->argument('correo');
        $this->line('');
        $this->info('=== SIMULACIÓN DE PEDIDO — destino de correos: ' . $correo . ' ===');

        // 0) Test de envío
        $this->line('0) Probando envío de correo...');
        try {
            Mail::raw('Prueba de envío Bletia — simulación de pedido.', fn ($m) => $m->to($correo)->subject('Prueba Bletia'));
            $this->info('   ✓ Correo de prueba enviado (revisa tu bandeja y SPAM).');
        } catch (\Throwable $e) {
            $this->error('   ✗ ERROR de envío: ' . $e->getMessage());
            $this->warn('   Revisa Brevo (IP autorizada). Aborto para no dejar a medias.');
            return self::FAILURE;
        }
        if ($this->option('solo-test')) return self::SUCCESS;

        // 1) Cliente demo
        $this->line('1) Cliente y transportista demo...');
        $cliId = DB::table('clientes')->where('email', $correo)->value('id');
        if (! $cliId) {
            $datos = ['email' => $correo, 'nombre' => 'Cliente Demo', 'created_at' => now(), 'updated_at' => now()];
            foreach (['telefono' => '0999000000', 'celular' => '0999000000', 'ciudad' => 'Cuenca', 'provincia' => 'Azuay', 'direccion' => 'Av. Demo y Pruebas', 'identificacion' => '0000000000', 'tipo_id' => 'cedula'] as $k => $v) {
                if (Schema::hasColumn('clientes', $k)) $datos[$k] = $v;
            }
            if (Schema::hasColumn('clientes', 'password')) $datos['password'] = Hash::make(Str::random(20));
            $cliId = DB::table('clientes')->insertGetId($datos);
            $this->info('   ✓ Cliente Demo creado (id ' . $cliId . ')');
        } else {
            $this->info('   ✓ Cliente con tu correo ya existe (id ' . $cliId . ')');
        }

        // Transportista demo con tu correo
        $tr = Transportista::where('email', $correo)->first();
        if (! $tr) {
            $tr = Transportista::create(array_filter([
                'nombre' => 'Transporte Demo', 'email' => $correo, 'celular' => '0999000000',
                'activo' => 1, 'tipo_identificacion' => 'ruc', 'identificacion' => '0190000000001',
                'direccion' => 'Terminal Demo', 'celular2' => '0988000000',
            ], fn ($v) => $v !== null));
            $this->info('   ✓ Transporte Demo creado (id ' . $tr->id . ')');
        } else {
            $this->info('   ✓ Transportista con tu correo ya existe (id ' . $tr->id . ')');
        }

        // 2) Pedido
        $this->line('2) Creando pedido de prueba...');
        $prod = DB::table('productos')->orderBy('id')->first();
        $prov = DB::table('proveedores')->orderBy('id')->first();
        $pedidoData = [
            'cliente_id' => $cliId, 'email' => $correo,
            'estado' => 'pendiente', 'estado_erp' => 'borrador', 'tipo_erp' => 'pedido_especial',
            'subtotal' => 0, 'iva' => 0, 'total' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ];
        if (Schema::hasColumn('pedidos', 'codigo')) $pedidoData['codigo'] = 'DEMO-' . now()->format('ymdHis');
        $pedidoData = array_intersect_key($pedidoData, array_flip(Schema::getColumnListing('pedidos')));
        $pedidoId = DB::table('pedidos')->insertGetId($pedidoData);
        $this->info('   ✓ Pedido #' . $pedidoId . ' creado');

        // 3) Ítem con specs
        $precio = (float) ($prod->precio ?? 1200);
        $cant = 1;
        $itemData = [
            'pedido_id' => $pedidoId, 'producto_id' => $prod->id ?? null,
            'nombre' => $prod->nombre ?? 'Producto demo', 'cantidad' => $cant,
            'precio' => $precio, 'subtotal' => $precio * $cant,
            'proveedor_id' => $prov->id ?? null, 'bultos' => 2,
            'tapiz_principal' => 'Lino Arena', 'tapiz_secundario' => 'Verde Oliva',
            'cojines' => '2 cojines plumas', 'lacado' => 'Nogal mate',
            'notas_adicionales' => 'Pedido de prueba — simulación ERP.',
            'created_at' => now(), 'updated_at' => now(),
        ];
        if (Schema::hasColumn('pedido_items', 'iva_rate')) $itemData['iva_rate'] = 15;
        $itemData = array_intersect_key($itemData, array_flip(Schema::getColumnListing('pedido_items')));
        DB::table('pedido_items')->insert($itemData);
        // totales
        DB::table('pedidos')->where('id', $pedidoId)->update([
            'subtotal' => $precio * $cant, 'total' => $precio * $cant,
        ]);
        $this->info('   ✓ Ítem agregado (' . ($prod->nombre ?? '') . ', tapiz/lacado/bultos)');

        $pedido = PedidoEspecial::find($pedidoId);

        // 4) Avanzar estados (correos al cliente)
        $this->line('4) Avanzando estados (correos al cliente)...');
        foreach (['confirmado', 'en_proceso'] as $st) {
            try { EstadoPedidoErp::avanzar($pedido, $st, true); $this->info('   ✓ Estado -> ' . $st . ' (correo enviado)'); }
            catch (\Throwable $e) { $this->warn('   · estado ' . $st . ': ' . $e->getMessage()); }
            $pedido->refresh();
        }

        // 5) Enviar a proveedor (orden PDF + correo)
        $this->line('5) Enviando a proveedor...');
        try {
            $r = EstadoPedidoErp::enviarAProveedor($pedido);
            $this->info('   ' . (($r['ok'] ?? false) ? '✓ Orden enviada a proveedor: ' . implode(', ', $r['proveedores'] ?? []) : '· ' . ($r['msg'] ?? 'sin envío')));
        } catch (\Throwable $e) { $this->warn('   · proveedor: ' . $e->getMessage()); }

        // 6) Despacho por transportista
        $this->line('6) Creando despacho por transportista...');
        $desp = Despacho::create([
            'pedido_id' => $pedidoId, 'ruta' => 'transportista', 'transportista_id' => $tr->id,
            'estado' => 'programado', 'fecha_programada' => now()->addDay(), 'listo' => true,
            'conductor_nombre' => 'Juan Pérez', 'conductor_nui' => '0102030405',
            'conductor_celular' => '0991112222', 'conductor_correo' => $correo, 'placa' => 'ABC-1234',
        ]);
        $this->info('   ✓ Despacho #' . $desp->id . ' (listo). Despachando...');
        try {
            $r = DespachoErp::notificar($desp->fresh());
            if ($r['ok'] ?? false) {
                $this->info('   ✓ Despachado. Correos: ' . implode(', ', $r['enviados'] ?: ['ninguno']));
                $this->line('   → Link de confirmación: ' . ($r['confirm'] ?? '—'));
            } else {
                $this->warn('   · ' . ($r['msg'] ?? 'sin notificación'));
            }
        } catch (\Throwable $e) { $this->error('   ✗ despacho: ' . $e->getMessage()); }

        // 7) Resumen
        $this->line('');
        $this->info('=== LISTO ===');
        $this->line('Pedido #' . $pedidoId);
        $this->line('Seguimiento: ' . url('/seguimiento?p=' . $pedidoId));
        $this->line('PDFs en: storage/app/public/erp/' . $pedidoId . '/');
        $this->line('Revisa tu correo ' . $correo . ' (y SPAM): prueba, confirmación de estado, orden, y despacho.');
        $this->line('Abre el link de confirmación para subir 2 fotos y cerrar como ENTREGADO.');
        return self::SUCCESS;
    }
}
