<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('blog_categorias', function (Blueprint $t) {
            $t->id();
            $t->string('nombre');
            $t->string('slug')->unique();
            $t->unsignedInteger('orden')->default(0);
            $t->boolean('activo')->default(true);
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('blog_categorias'); }
};
