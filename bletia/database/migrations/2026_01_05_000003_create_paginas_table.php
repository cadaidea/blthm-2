<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('paginas', function (Blueprint $t) {
            $t->id();
            $t->string('titulo');
            $t->string('slug')->unique();
            $t->longText('contenido')->nullable();
            $t->string('imagen')->nullable();
            $t->boolean('activo')->default(true);
            $t->boolean('mostrar_en_menu')->default(false);
            $t->unsignedInteger('orden')->default(0);
            $t->string('meta_title')->nullable();
            $t->string('meta_description', 320)->nullable();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('paginas'); }
};
