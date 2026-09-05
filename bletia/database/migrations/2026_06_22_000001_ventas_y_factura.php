<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('ventas')) {
            Schema::create('ventas', function (Blueprint $t) {
                $t->id();
                $t->foreignId('pedido_id')->nullable()->index();
                $t->string('nro_factura')->index();
                $t->string('folio')->nullable();
                $t->date('fecha')->index();
                $t->foreignId('cliente_id')->nullable()->index();
                $t->foreignId('local_id')->nullable()->index();
                $t->foreignId('vendedor_id')->nullable()->index();
                $t->string('forma_venta')->nullable();      // online/stock/local
                $t->decimal('subtotal', 12, 2)->default(0);  // base sin IVA
                $t->decimal('iva', 12, 2)->default(0);
                $t->decimal('total', 12, 2)->default(0);     // PVP (IVA incl.)
                $t->string('estado')->default('emitida');    // emitida/anulada
                $t->unsignedBigInteger('facturado_por')->nullable();
                $t->timestamp('facturado_at')->nullable();
                $t->timestamps();
            });
        }

        Schema::table('pedidos', function (Blueprint $t) {
            if (! Schema::hasColumn('pedidos', 'nro_factura')) $t->string('nro_factura')->nullable()->index();
            if (! Schema::hasColumn('pedidos', 'facturado_at')) $t->timestamp('facturado_at')->nullable();
            if (! Schema::hasColumn('pedidos', 'facturado_por')) $t->unsignedBigInteger('facturado_por')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
        Schema::table('pedidos', function (Blueprint $t) {
            foreach (['nro_factura', 'facturado_at', 'facturado_por'] as $c) {
                if (Schema::hasColumn('pedidos', $c)) $t->dropColumn($c);
            }
        });
    }
};
