<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('stock', function (Blueprint $t) {
            $t->id();
            $t->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $t->foreignId('local_id')->constrained('locales')->cascadeOnDelete();
            $t->integer('cantidad')->default(0);
            $t->integer('minimo')->default(0);
            $t->timestamps();
            $t->unique(['producto_id', 'local_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('stock'); }
};
