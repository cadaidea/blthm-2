<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (! Schema::hasTable('despachos')) {
            Schema::create('despachos', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('pedido_id')->index();
                $t->enum('ruta', ['retiro_local', 'transportista'])->default('retiro_local');
                $t->unsignedBigInteger('transportista_id')->nullable();
                $t->unsignedBigInteger('local_retiro_id')->nullable();
                $t->enum('estado', ['programado', 'en_transito', 'entregado', 'cancelado'])->default('programado');
                $t->timestamp('fecha_programada')->nullable();
                $t->unsignedBigInteger('link_id')->nullable();
                $t->text('notas')->nullable();
                $t->timestamps();
            });
        }
        if (Schema::hasTable('pedidos') && ! Schema::hasColumn('pedidos', 'estado_erp')) {
            Schema::table('pedidos', function (Blueprint $t) { $t->string('estado_erp', 40)->nullable()->index(); });
        }
    }
    public function down(): void { Schema::dropIfExists('despachos'); }
};
