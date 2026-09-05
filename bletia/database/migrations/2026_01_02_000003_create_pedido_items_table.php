<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('pedido_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $t->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $t->string('nombre');
            $t->decimal('precio', 12, 2)->default(0);  // neto unitario
            $t->decimal('iva_rate', 5, 2)->default(0);
            $t->integer('cantidad')->default(1);
            $t->decimal('subtotal', 12, 2)->default(0); // neto * cantidad
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('pedido_items'); }
};
