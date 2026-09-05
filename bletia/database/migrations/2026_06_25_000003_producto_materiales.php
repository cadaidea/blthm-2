<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (! Schema::hasTable('producto_materiales')) {
            Schema::create('producto_materiales', function (Blueprint $t) {
                $t->id();
                $t->foreignId('producto_id')->index();
                $t->foreignId('materia_prima_id')->index();
                $t->decimal('cantidad', 12, 3)->default(0);
                $t->string('nota')->nullable();
                $t->timestamps();
                $t->unique(['producto_id', 'materia_prima_id']);
            });
        }
    }
    public function down(): void { Schema::dropIfExists('producto_materiales'); }
};
