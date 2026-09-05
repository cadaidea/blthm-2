<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('proveedores')) return;
        Schema::create('proveedores', function (Blueprint $t) {
            $t->id();
            $t->string('nombre');
            $t->string('email')->nullable();
            $t->string('contacto')->nullable();
            $t->string('telefono', 40)->nullable();
            $t->string('ciudad', 80)->nullable();
            $t->text('notas')->nullable();
            $t->boolean('activo')->default(true);
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('proveedores'); }
};
