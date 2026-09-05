<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (! Schema::hasTable('cupones')) {
            Schema::create('cupones', function (Blueprint $t) {
                $t->id();
                $t->string('codigo')->unique();
                $t->string('tipo')->default('porcentaje');   // porcentaje | fijo
                $t->decimal('valor', 10, 2)->default(0);      // % o USD
                $t->string('audiencia')->default('primera_compra'); // primera_compra | recurrente | todos
                $t->boolean('activo')->default(true);
                $t->unsignedInteger('limite_global')->nullable(); // máx usos totales
                $t->date('vence_at')->nullable();
                $t->decimal('minimo_compra', 10, 2)->nullable(); // total mínimo para aplicar
                $t->unsignedInteger('usos')->default(0);
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('cupon_usos')) {
            Schema::create('cupon_usos', function (Blueprint $t) {
                $t->id();
                $t->foreignId('cupon_id')->index();
                $t->foreignId('cliente_id')->index();
                $t->foreignId('pedido_id')->nullable();
                $t->decimal('monto', 10, 2)->default(0);
                $t->timestamps();
                $t->unique(['cupon_id', 'cliente_id']); // 1 uso por cliente por cupón
            });
        }
        Schema::table('pedidos', function (Blueprint $t) {
            if (! Schema::hasColumn('pedidos','cupon_id'))      $t->unsignedBigInteger('cupon_id')->nullable();
            if (! Schema::hasColumn('pedidos','cupon_codigo'))  $t->string('cupon_codigo')->nullable();
            if (! Schema::hasColumn('pedidos','descuento'))     $t->decimal('descuento', 10, 2)->default(0);
        });
    }
    public function down(): void {
        Schema::dropIfExists('cupon_usos');
        Schema::dropIfExists('cupones');
        Schema::table('pedidos', function (Blueprint $t) {
            foreach (['cupon_id','cupon_codigo','descuento'] as $c) if (Schema::hasColumn('pedidos',$c)) $t->dropColumn($c);
        });
    }
};
