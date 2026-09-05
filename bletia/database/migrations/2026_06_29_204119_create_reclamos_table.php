<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reclamos')) return;
        Schema::create('reclamos', function (Blueprint $table) {
            $table->id();
            $table->string('folio')->nullable()->index();          // RCL-000001
            $table->unsignedBigInteger('pedido_id')->nullable()->index();
            $table->unsignedBigInteger('cliente_id')->nullable()->index();
            $table->string('producto')->nullable();                 // qué mueble
            $table->string('tipo_problema')->nullable();            // tapiz, estructura, esponja, medida, otro
            $table->text('descripcion')->nullable();                // detalle del reclamo
            $table->json('fotos')->nullable();                      // fotos del problema
            $table->string('estado')->default('abierto');           // abierto|en_revision|en_reparacion|resuelto|rechazado
            $table->string('resolucion')->nullable();               // reparacion|reposicion|nota_credito|reembolso|sin_garantia
            $table->text('resolucion_nota')->nullable();
            $table->decimal('costo', 12, 2)->default(0);            // costo de la solución (interno)
            $table->unsignedBigInteger('atendido_por')->nullable();
            $table->timestamp('resuelto_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reclamos');
    }
};
