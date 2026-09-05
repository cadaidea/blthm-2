<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('etiquetas', function (Blueprint $t) {
            $t->id(); $t->string('nombre'); $t->string('slug')->unique(); $t->timestamps();
        });
        Schema::create('articulo_etiqueta', function (Blueprint $t) {
            $t->foreignId('articulo_id')->constrained('articulos')->cascadeOnDelete();
            $t->foreignId('etiqueta_id')->constrained('etiquetas')->cascadeOnDelete();
            $t->primary(['articulo_id', 'etiqueta_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('articulo_etiqueta'); Schema::dropIfExists('etiquetas'); }
};
