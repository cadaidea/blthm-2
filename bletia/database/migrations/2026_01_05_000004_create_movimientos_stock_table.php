<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('movimientos_stock', function (Blueprint $t) {
            $t->id();
            $t->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $t->foreignId('local_id')->constrained('locales')->cascadeOnDelete();
            $t->foreignId('local_destino_id')->nullable()->constrained('locales')->nullOnDelete();
            $t->string('tipo', 20); // entrada | salida | ajuste | transferencia
            $t->integer('cantidad')->default(0);
            $t->string('referencia')->nullable();
            $t->string('nota')->nullable();
            $t->timestamps();
            $t->index(['producto_id', 'local_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('movimientos_stock'); }
};
