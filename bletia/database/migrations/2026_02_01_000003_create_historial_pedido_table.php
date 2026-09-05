<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('historial_pedido')) return;
        Schema::create('historial_pedido', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('pedido_id')->index();
            $t->string('estado_anterior', 40)->nullable();
            $t->string('estado_nuevo', 40)->nullable();
            $t->unsignedBigInteger('usuario_id')->nullable();
            $t->text('notas')->nullable();
            $t->timestamp('creado_en')->nullable();
        });
    }
    public function down(): void { Schema::dropIfExists('historial_pedido'); }
};
