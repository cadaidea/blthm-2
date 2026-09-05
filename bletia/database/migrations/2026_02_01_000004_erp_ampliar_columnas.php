<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('locales')) {
            Schema::table('locales', function (Blueprint $t) {
                if (! Schema::hasColumn('locales', 'tipo')) $t->string('tipo', 30)->default('local_venta')->after('nombre');
            });
        }
        if (Schema::hasTable('productos')) {
            Schema::table('productos', function (Blueprint $t) {
                if (! Schema::hasColumn('productos', 'bultos_default')) $t->unsignedInteger('bultos_default')->default(1);
                if (! Schema::hasColumn('productos', 'proveedor_default_id')) { $t->unsignedBigInteger('proveedor_default_id')->nullable()->index(); }
            });
        }
        if (Schema::hasTable('clientes')) {
            Schema::table('clientes', function (Blueprint $t) {
                foreach ([
                    'cedula_ruc' => fn () => $t->string('cedula_ruc', 20)->nullable()->index(),
                    'celular'    => fn () => $t->string('celular', 40)->nullable(),
                    'ciudad'     => fn () => $t->string('ciudad', 80)->nullable(),
                    'provincia'  => fn () => $t->string('provincia', 80)->nullable(),
                    'direccion'  => fn () => $t->string('direccion', 255)->nullable(),
                    'notas'      => fn () => $t->text('notas')->nullable(),
                    'woo_customer_id' => fn () => $t->unsignedBigInteger('woo_customer_id')->nullable()->index(),
                ] as $col => $add) {
                    if (! Schema::hasColumn('clientes', $col)) $add();
                }
            });
        }
    }
    public function down(): void {}
};
