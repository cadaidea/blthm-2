<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('pedidos') && ! Schema::hasColumn('pedidos', 'tipo_erp')) {
            Schema::table('pedidos', function (Blueprint $t) { $t->string('tipo_erp', 30)->nullable()->index(); });
        }
        if (Schema::hasTable('pedido_items')) {
            Schema::table('pedido_items', function (Blueprint $t) {
                foreach ([
                    'proveedor_id'     => fn () => $t->unsignedBigInteger('proveedor_id')->nullable()->index(),
                    'bultos'           => fn () => $t->unsignedInteger('bultos')->nullable(),
                    'tapiz_principal'  => fn () => $t->string('tapiz_principal')->nullable(),
                    'tapiz_secundario' => fn () => $t->string('tapiz_secundario')->nullable(),
                    'cojines'          => fn () => $t->string('cojines')->nullable(),
                    'lacado'           => fn () => $t->string('lacado')->nullable(),
                    'notas_adicionales' => fn () => $t->text('notas_adicionales')->nullable(),
                    'local_origen_id'  => fn () => $t->unsignedBigInteger('local_origen_id')->nullable(),
                    'fotos_ref'        => fn () => $t->json('fotos_ref')->nullable(),
                ] as $col => $add) {
                    if (! Schema::hasColumn('pedido_items', $col)) $add();
                }
            });
        }
    }
    public function down(): void {}
};
