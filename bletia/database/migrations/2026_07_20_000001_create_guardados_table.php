<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('guardados')) return;
        Schema::create('guardados', function (Blueprint $t) {
            $t->id();
            $t->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $t->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['cliente_id', 'producto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardados');
    }
};
