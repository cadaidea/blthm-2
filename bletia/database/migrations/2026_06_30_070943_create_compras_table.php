<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('compras')) {
            Schema::create('compras', function (Blueprint $table) {
                $table->id();
                $table->string('folio')->nullable()->index();              // OC-000001
                $table->string('tipo', 20);                                 // proveedor | produccion_interna
                $table->unsignedBigInteger('proveedor_id')->nullable();     // solo si tipo=proveedor
                $table->unsignedBigInteger('local_destino_id')->nullable(); // a qué local/bodega entra el stock
                $table->string('estado', 20)->default('creada');           // creada|en_proceso|listo_envio|en_transito|recibida|anulada
                // documento de respaldo del proveedor (o nada, si es producción interna sin factura de insumos)
                $table->string('doc_tipo', 20)->nullable();                 // factura | nota_venta | ninguno
                $table->string('doc_numero')->nullable();                   // número del documento del proveedor
                $table->date('doc_fecha')->nullable();
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('iva', 12, 2)->default(0);
                $table->decimal('total', 12, 2)->default(0);
                $table->text('notas')->nullable();
                $table->unsignedBigInteger('creado_por')->nullable();
                $table->timestamp('recibida_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('compra_items')) {
            Schema::create('compra_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('compra_id');
                $table->unsignedBigInteger('producto_id')->nullable();
                $table->unsignedBigInteger('variante_id')->nullable();
                $table->string('nombre');
                $table->decimal('cantidad', 12, 2)->default(1);
                $table->decimal('costo_unitario', 12, 2)->default(0); // costo SIN IVA
                $table->decimal('iva_rate', 5, 2)->default(15);
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->timestamps();
                $table->index('compra_id');
            });
        }

        if (! Schema::hasTable('compra_pagos')) {
            Schema::create('compra_pagos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('compra_id');
                $table->decimal('monto', 12, 2);
                $table->string('metodo', 20); // efectivo|transferencia|deposito|cheque|tarjeta
                $table->date('fecha');
                $table->string('tipo_tarjeta')->nullable();
                $table->string('tarjeta_naturaleza')->nullable();
                $table->string('cheque_girador')->nullable();
                $table->string('cheque_numero')->nullable();
                $table->string('cheque_banco')->nullable();
                $table->date('cheque_fecha_cobro')->nullable();
                $table->string('cheque_estado', 20)->default('pendiente');
                $table->string('nro_comprobante')->nullable();
                $table->json('comprobantes')->nullable();
                $table->text('nota')->nullable();
                $table->unsignedBigInteger('creado_por')->nullable();
                $table->timestamps();
                $table->index('compra_id');
            });
        }

        // vincular despachos a una compra (igual patrón que reclamo_id)
        Schema::table('despachos', function (Blueprint $table) {
            if (! Schema::hasColumn('despachos', 'compra_id')) {
                $table->unsignedBigInteger('compra_id')->nullable()->after('reclamo_id');
            }
        });

        // vincular links_unicos a una compra también (para confirmación de proveedor)
        Schema::table('links_unicos', function (Blueprint $table) {
            if (! Schema::hasColumn('links_unicos', 'compra_id')) {
                $table->unsignedBigInteger('compra_id')->nullable()->after('reclamo_id');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compra_pagos');
        Schema::dropIfExists('compra_items');
        Schema::dropIfExists('compras');
        Schema::table('despachos', fn ($t) => $t->dropColumn('compra_id'));
        Schema::table('links_unicos', fn ($t) => $t->dropColumn('compra_id'));
    }
};
