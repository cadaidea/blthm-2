<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('atributos', function (Blueprint $t) {
            $t->id();
            $t->string('nombre');                 // Tapiz, Lacado, Lado
            $t->string('tipo', 20)->default('color'); // color | imagen | texto
            $t->unsignedInteger('orden')->default(0);
            $t->timestamps();
        });
        Schema::create('atributo_opciones', function (Blueprint $t) {
            $t->id();
            $t->foreignId('atributo_id')->constrained('atributos')->cascadeOnDelete();
            $t->string('valor');                  // "Lino beige", "Left", "Nogal"
            $t->string('color', 20)->nullable();
            $t->string('imagen')->nullable();
            $t->unsignedInteger('orden')->default(0);
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('atributo_opciones'); Schema::dropIfExists('atributos'); }
};
