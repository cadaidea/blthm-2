<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('producto_imagenes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $t->string('ruta');
            $t->string('alt')->nullable();
            $t->unsignedInteger('orden')->default(0);
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('producto_imagenes'); }
};
