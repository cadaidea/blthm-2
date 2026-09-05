<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('categorias', function (Blueprint $t) {
            $t->id();
            $t->foreignId('parent_id')->nullable()->constrained('categorias')->nullOnDelete();
            $t->string('nombre');
            $t->string('slug')->unique();
            $t->text('descripcion')->nullable();
            $t->unsignedInteger('orden')->default(0);
            $t->boolean('activo')->default(true);
            $t->string('meta_title')->nullable();
            $t->string('meta_description', 320)->nullable();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('categorias'); }
};
