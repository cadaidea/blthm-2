<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // destino de fabricación en el pedido
        Schema::table('pedidos', function (Blueprint $t) {
            if (! Schema::hasColumn('pedidos', 'destino_fab')) $t->string('destino_fab')->nullable(); // proveedor | interno
        });

        // materias primas (inventario del taller)
        if (! Schema::hasTable('materias_primas')) {
            Schema::create('materias_primas', function (Blueprint $t) {
                $t->id();
                $t->string('nombre');
                $t->string('unidad')->default('u');     // u, m, m2, kg, lt...
                $t->decimal('stock', 12, 2)->default(0);
                $t->decimal('minimo', 12, 2)->default(0);
                $t->decimal('costo', 12, 2)->nullable();
                $t->boolean('activo')->default(true);
                $t->timestamps();
            });
        }

        // movimientos de material (solicitud / entrega / uso)
        if (! Schema::hasTable('movimientos_material')) {
            Schema::create('movimientos_material', function (Blueprint $t) {
                $t->id();
                $t->foreignId('materia_prima_id')->constrained('materias_primas')->cascadeOnDelete();
                $t->unsignedBigInteger('pedido_id')->nullable();
                $t->string('tipo');                      // solicitud | entrega | uso | entrada | ajuste
                $t->decimal('cantidad', 12, 2)->default(0);
                $t->string('estado')->nullable();        // solicitado | entregado (para solicitudes)
                $t->string('nota')->nullable();
                $t->unsignedBigInteger('user_id')->nullable();
                $t->timestamps();
            });
        }
    }
    public function down(): void {}
};
