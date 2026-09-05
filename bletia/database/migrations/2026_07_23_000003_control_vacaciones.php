<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('empleados', function (Blueprint $t) {
            if (! Schema::hasColumn('empleados', 'dias_vacaciones_anuales')) {
                $t->decimal('dias_vacaciones_anuales', 5, 2)->default(15)->after('cargas_familiares');
            }
        });

        if (! Schema::hasTable('vacaciones_tomadas')) {
            Schema::create('vacaciones_tomadas', function (Blueprint $t) {
                $t->id();
                $t->string('folio', 20)->nullable();
                $t->foreignId('empleado_id')->constrained('empleados');
                $t->date('fecha_inicio');
                $t->date('fecha_fin');
                $t->decimal('dias', 5, 2); // calendario, inclusive (Art. 69 Código de Trabajo)
                $t->text('nota')->nullable();
                $t->string('adjunto')->nullable(); // solicitud/autorización firmada
                $t->string('estado', 12)->default('registrada'); // registrada | anulada
                $t->unsignedBigInteger('creado_por')->nullable();
                $t->timestamps();
                $t->index(['empleado_id', 'estado']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vacaciones_tomadas');
        Schema::table('empleados', function (Blueprint $t) {
            if (Schema::hasColumn('empleados', 'dias_vacaciones_anuales')) $t->dropColumn('dias_vacaciones_anuales');
        });
    }
};
