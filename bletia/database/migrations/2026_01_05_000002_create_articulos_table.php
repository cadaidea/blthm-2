<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('articulos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('blog_categoria_id')->nullable()->constrained('blog_categorias')->nullOnDelete();
            $t->string('titulo');
            $t->string('slug')->unique();
            $t->string('autor')->nullable();
            $t->text('extracto')->nullable();
            $t->longText('contenido')->nullable();
            $t->string('imagen')->nullable();
            $t->boolean('activo')->default(true);
            $t->timestamp('publicado_at')->nullable();
            $t->string('meta_title')->nullable();
            $t->string('meta_description', 320)->nullable();
            $t->timestamps();
            $t->index(['activo', 'publicado_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('articulos'); }
};
