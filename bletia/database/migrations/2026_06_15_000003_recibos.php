<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('recibos')) return;

        Schema::create('recibos', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('pedido_id')->index();
            $t->unsignedBigInteger('cliente_id')->nullable()->index();
            $t->string('tipo', 20)->default('abono');   // abono | pago
            $t->decimal('monto', 10, 2)->default(0);
            $t->string('metodo', 30)->nullable();        // efectivo | transferencia | tarjeta | deposito | otro
            $t->date('fecha')->nullable();
            $t->text('nota')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void {}
};
