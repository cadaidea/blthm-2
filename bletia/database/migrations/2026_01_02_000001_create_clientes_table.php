<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('clientes', function (Blueprint $t) {
            $t->id();
            $t->string('identificacion', 20)->nullable();
            $t->string('tipo_id', 10)->default('cedula'); // cedula | ruc | pasaporte
            $t->string('nombre');
            $t->string('email')->nullable();
            $t->string('telefono', 40)->nullable();
            $t->string('direccion')->nullable();
            $t->string('ciudad', 100)->nullable();
            $t->timestamps();
            $t->index('identificacion');
            $t->index('email');
        });
    }
    public function down(): void { Schema::dropIfExists('clientes'); }
};
