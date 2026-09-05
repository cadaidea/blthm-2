<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ---- GASTOS ----
        if (! Schema::hasTable('gastos')) {
            Schema::create('gastos', function (Blueprint $t) {
                $t->id();
                $t->string('folio', 20)->nullable();
                $t->date('fecha');
                $t->string('categoria', 40);              // combustible, transporte, viaticos, ...
                $t->unsignedBigInteger('proveedor_id')->nullable();
                $t->string('beneficiario', 160)->nullable(); // si no es proveedor registrado
                $t->string('beneficiario_id_num', 20)->nullable(); // RUC/cédula del beneficiario
                $t->string('doc_tipo', 20)->nullable();   // factura, nota_venta, recibo, liquidacion
                $t->string('doc_numero', 30)->nullable();
                $t->string('autorizacion_sri', 60)->nullable();
                $t->decimal('base', 12, 2)->default(0);
                $t->decimal('iva', 12, 2)->default(0);
                $t->decimal('ret_iva', 12, 2)->default(0);
                $t->decimal('ret_renta', 12, 2)->default(0);
                $t->decimal('total', 12, 2)->default(0);
                $t->string('forma_pago', 20)->default('contado'); // contado | credito
                $t->string('metodo_pago', 20)->nullable();        // efectivo, transferencia, tarjeta, cheque
                $t->text('notas')->nullable();
                $t->string('adjunto')->nullable();
                $t->string('estado', 12)->default('registrado');  // registrado | anulado
                $t->unsignedBigInteger('creado_por')->nullable();
                $t->timestamps();
                $t->index(['fecha', 'categoria']);
            });
        }

        // ---- MAPEOS evento/categoría -> cuenta contable (configurable) ----
        if (! Schema::hasTable('cuenta_mapeos')) {
            Schema::create('cuenta_mapeos', function (Blueprint $t) {
                $t->id();
                $t->string('clave', 60)->unique();   // ej: venta.cxc, gasto.combustible
                $t->string('descripcion', 160);
                $t->string('codigo_cuenta', 20);     // código en el plan de cuentas
                $t->timestamps();
            });
        }

        $this->seedMapeos();
    }

    protected function seedMapeos(): void
    {
        if (DB::table('cuenta_mapeos')->count() > 0) return;

        $m = [
            // Ventas
            ['venta.cxc', 'Venta · cuenta por cobrar (cliente)', '1.1.02.01'],
            ['venta.ingreso', 'Venta · ingreso por ventas', '4.1.01'],
            ['venta.iva', 'Venta · IVA en ventas por pagar', '2.1.02.01'],
            // Cobros (por método)
            ['cobro.efectivo', 'Cobro en efectivo', '1.1.01.01'],
            ['cobro.transferencia', 'Cobro por transferencia', '1.1.01.03'],
            ['cobro.tarjeta', 'Cobro con tarjeta', '1.1.01.03'],
            ['cobro.cheque', 'Cobro con cheque', '1.1.02.02'],
            ['cobro.cxc', 'Cobro · baja de cuenta por cobrar', '1.1.02.01'],
            // Compras (inventario perpetuo)
            ['compra.inventario', 'Compra · inventario', '1.1.04.01'],
            ['compra.iva', 'Compra · IVA crédito tributario', '1.1.03.01'],
            ['compra.cxp', 'Compra · cuenta por pagar (proveedor)', '2.1.01.01'],
            // Pago a proveedor (por método)
            ['pago.efectivo', 'Pago proveedor en efectivo', '1.1.01.01'],
            ['pago.transferencia', 'Pago proveedor transferencia', '1.1.01.03'],
            ['pago.tarjeta', 'Pago proveedor tarjeta', '1.1.01.03'],
            ['pago.cheque', 'Pago proveedor cheque', '1.1.01.03'],
            ['pago.cxp', 'Pago · baja de cuenta por pagar', '2.1.01.01'],
            // Gastos por categoría -> cuenta 6.1.x
            ['gasto.combustible', 'Gasto combustible', '6.1.07'],
            ['gasto.transporte', 'Gasto transporte y flete', '6.1.07'],
            ['gasto.viaticos', 'Gasto viáticos (alim./hosp./viajes)', '6.1.11'],
            ['gasto.marketing', 'Gasto marketing y publicidad', '6.1.06'],
            ['gasto.servicios_basicos', 'Gasto servicios básicos', '6.1.05'],
            ['gasto.arriendo', 'Gasto arriendo', '6.1.04'],
            ['gasto.suministros', 'Gasto suministros y materiales', '6.1.09'],
            ['gasto.comisiones', 'Gasto comisiones bancarias', '6.1.10'],
            ['gasto.sueldos', 'Gasto sueldos y salarios', '6.1.01'],
            ['gasto.varios', 'Gasto varios', '6.1.11'],
            ['gasto.iva', 'Gasto · IVA crédito tributario', '1.1.03.01'],
            ['gasto.cxp', 'Gasto a crédito · cuenta por pagar', '2.1.01.01'],
            // Caja/banco default para gastos al contado
            ['pago_gasto.efectivo', 'Pago gasto efectivo', '1.1.01.01'],
            ['pago_gasto.transferencia', 'Pago gasto transferencia', '1.1.01.03'],
            ['pago_gasto.tarjeta', 'Pago gasto tarjeta', '1.1.01.03'],
            ['pago_gasto.cheque', 'Pago gasto cheque', '1.1.01.03'],
        ];
        $rows = [];
        foreach ($m as [$clave, $desc, $cod]) {
            $rows[] = ['clave' => $clave, 'descripcion' => $desc, 'codigo_cuenta' => $cod, 'created_at' => now(), 'updated_at' => now()];
        }
        DB::table('cuenta_mapeos')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('gastos');
        Schema::dropIfExists('cuenta_mapeos');
    }
};
