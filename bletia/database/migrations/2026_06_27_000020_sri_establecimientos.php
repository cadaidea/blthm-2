<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sri_establecimientos')) {
            Schema::create('sri_establecimientos', function (Blueprint $t) {
                $t->id();
                $t->string('codigo', 3);           // 001, 002...
                $t->string('nombre')->nullable();   // "Matriz", "Sucursal Centro"
                $t->string('direccion');
                $t->boolean('activo')->default(true);
                $t->timestamps();
                $t->unique('codigo');
            });
        }
        if (! Schema::hasTable('sri_puntos_emision')) {
            Schema::create('sri_puntos_emision', function (Blueprint $t) {
                $t->id();
                $t->foreignId('establecimiento_id')->constrained('sri_establecimientos')->cascadeOnDelete();
                $t->string('codigo', 3);           // 001, 002...
                $t->string('nombre')->nullable();   // "Caja 1", "Online"
                $t->boolean('activo')->default(true);
                $t->timestamps();
                $t->unique(['establecimiento_id', 'codigo']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sri_puntos_emision');
        Schema::dropIfExists('sri_establecimientos');
    }
};
