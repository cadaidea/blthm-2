<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('pedidos', function (Blueprint $t) {
            $t->id();
            $t->string('codigo', 30)->unique();
            $t->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $t->string('estado', 30)->default('pendiente_pago'); // pendiente_pago | pagado | rechazado
            $t->decimal('subtotal', 12, 2)->default(0);
            $t->decimal('iva', 12, 2)->default(0);
            $t->decimal('total', 12, 2)->default(0);
            // Trazabilidad Payphone
            $t->string('pp_client_tx', 40)->nullable()->index();
            $t->string('pp_transaction_id', 40)->nullable();
            $t->string('pp_auth', 40)->nullable();
            $t->string('email')->nullable();
            $t->timestamps();
            $t->index('estado');
        });
    }
    public function down(): void { Schema::dropIfExists('pedidos'); }
};
