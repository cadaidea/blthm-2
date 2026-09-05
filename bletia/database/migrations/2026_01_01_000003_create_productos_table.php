<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('productos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('categoria_id')->nullable()->constrained('categorias')->nullOnDelete();
            $t->string('nombre');
            $t->string('slug')->unique();
            $t->string('sku')->nullable()->index();
            $t->text('descripcion_corta')->nullable();
            $t->longText('descripcion')->nullable();
            $t->decimal('precio', 12, 2)->default(0);
            $t->decimal('iva_rate', 5, 2)->default(15); // IVA EC configurable
            $t->boolean('activo')->default(true);
            $t->boolean('destacado')->default(false);
            $t->string('meta_title')->nullable();
            $t->string('meta_description', 320)->nullable();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('productos'); }
};
