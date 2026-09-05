<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (! Schema::hasTable('woo_pedidos')) {
            Schema::create('woo_pedidos', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('woo_id')->unique();
                $t->string('numero', 40)->nullable();
                $t->string('estado', 30)->nullable();
                $t->decimal('total', 12, 2)->default(0);
                $t->string('moneda', 8)->nullable();
                $t->string('cliente_nombre')->nullable();
                $t->string('cliente_email')->nullable();
                $t->unsignedBigInteger('woo_customer_id')->nullable()->index();
                $t->timestamp('fecha')->nullable();
                $t->json('raw')->nullable();
                $t->timestamp('importado_en')->nullable();
            });
        }
        if (! Schema::hasTable('woo_pedido_items')) {
            Schema::create('woo_pedido_items', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('woo_pedido_id')->index();
                $t->string('producto_nombre')->nullable();
                $t->string('sku', 80)->nullable();
                $t->decimal('cantidad', 10, 2)->default(1);
                $t->decimal('precio', 12, 2)->default(0);
                $t->decimal('total', 12, 2)->default(0);
                $t->text('variaciones')->nullable();
            });
        }
    }
    public function down(): void {
        Schema::dropIfExists('woo_pedido_items');
        Schema::dropIfExists('woo_pedidos');
    }
};
