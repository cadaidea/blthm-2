<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (! Schema::hasTable('links_unicos')) {
            Schema::create('links_unicos', function (Blueprint $t) {
                $t->id();
                $t->string('token', 191)->unique();
                $t->string('tipo', 30); // transportista | cliente_retiro | proveedor
                $t->unsignedBigInteger('pedido_id')->nullable()->index();
                $t->unsignedBigInteger('despacho_id')->nullable()->index();
                $t->boolean('usado')->default(false);
                $t->unsignedInteger('intentos')->default(0);
                $t->timestamp('expira_en')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('confirmaciones')) {
            Schema::create('confirmaciones', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('link_id')->index();
                $t->unsignedBigInteger('despacho_id')->nullable();
                $t->unsignedBigInteger('pedido_id')->nullable();
                $t->string('receptor_nombre')->nullable();
                $t->string('receptor_cedula', 20)->nullable();
                $t->string('receptor_celular', 40)->nullable();
                $t->string('foto_1_url', 255)->nullable();
                $t->string('foto_2_url', 255)->nullable();
                $t->string('ip_origen', 45)->nullable();
                $t->timestamp('confirmado_en')->nullable();
            });
        }
    }
    public function down(): void {
        Schema::dropIfExists('confirmaciones');
        Schema::dropIfExists('links_unicos');
    }
};
