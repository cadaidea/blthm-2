<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Pagos de beneficios (décimos, fondos, vacaciones) — descarga de la provisión.
        if (! Schema::hasTable('pagos_beneficio')) {
            Schema::create('pagos_beneficio', function (Blueprint $t) {
                $t->id();
                $t->string('folio', 20)->nullable();
                $t->foreignId('empleado_id')->constrained('empleados');
                $t->string('tipo', 20);            // decimo_tercero | decimo_cuarto | fondos_reserva | vacaciones | liquidacion
                $t->string('periodo', 40)->nullable(); // ej "2026" o "abr2025-mar2026"
                $t->date('fecha');
                $t->decimal('monto', 12, 2);
                $t->string('metodo_pago', 20)->nullable();
                $t->string('nro_comprobante', 40)->nullable();
                $t->string('adjunto')->nullable();
                $t->text('detalle')->nullable();
                $t->string('estado', 12)->default('pagado'); // pagado | anulado
                $t->unsignedBigInteger('creado_por')->nullable();
                $t->timestamps();
                $t->index(['empleado_id', 'tipo']);
            });
        }

        // Liquidación de haberes (al salir el empleado) — cabecera con desglose.
        if (! Schema::hasTable('liquidaciones')) {
            Schema::create('liquidaciones', function (Blueprint $t) {
                $t->id();
                $t->string('folio', 20)->nullable();
                $t->foreignId('empleado_id')->constrained('empleados');
                $t->date('fecha');
                $t->date('fecha_salida');
                $t->string('motivo', 40)->nullable(); // renuncia | despido | mutuo_acuerdo | fin_contrato
                $t->decimal('decimo_tercero', 12, 2)->default(0);
                $t->decimal('decimo_cuarto', 12, 2)->default(0);
                $t->decimal('vacaciones', 12, 2)->default(0);
                $t->decimal('fondos_reserva', 12, 2)->default(0);
                $t->decimal('otros', 12, 2)->default(0);
                $t->decimal('descuentos', 12, 2)->default(0);
                $t->decimal('total', 12, 2)->default(0);
                $t->text('detalle')->nullable();
                $t->string('adjunto')->nullable();
                $t->string('estado', 12)->default('registrada'); // registrada | anulada
                $t->unsignedBigInteger('creado_por')->nullable();
                $t->timestamps();
            });
        }

        // mapeos contables de beneficios (por si difieren)
        if (Schema::hasTable('cuenta_mapeos')) {
            $nuevos = [
                ['beneficio.pagar_desde', 'Pago beneficio · baja de provisión', '2.1.03.03'],
                ['beneficio.banco', 'Pago beneficio · salida de banco', '1.1.01.03'],
                ['beneficio.caja', 'Pago beneficio · salida de caja', '1.1.01.01'],
            ];
            foreach ($nuevos as [$c, $d, $cod]) {
                if (! \Illuminate\Support\Facades\DB::table('cuenta_mapeos')->where('clave', $c)->exists()) {
                    \Illuminate\Support\Facades\DB::table('cuenta_mapeos')->insert(['clave' => $c, 'descripcion' => $d, 'codigo_cuenta' => $cod, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_beneficio');
        Schema::dropIfExists('liquidaciones');
    }
};
