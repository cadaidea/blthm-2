<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('empleados', function (Blueprint $t) {
            if (! Schema::hasColumn('empleados', 'region')) {
                // sierra_oriente (décimo 4º en marzo) | costa_galapagos (agosto)
                $t->string('region', 20)->default('sierra_oriente')->after('cargas_familiares');
            }
            if (! Schema::hasColumn('empleados', 'modo_decimo_tercero')) {
                $t->string('modo_decimo_tercero', 12)->default('acumulado')->after('region'); // mensualizado | acumulado
            }
            if (! Schema::hasColumn('empleados', 'modo_decimo_cuarto')) {
                $t->string('modo_decimo_cuarto', 12)->default('acumulado')->after('modo_decimo_tercero');
            }
            if (! Schema::hasColumn('empleados', 'modo_fondos_reserva')) {
                $t->string('modo_fondos_reserva', 12)->default('mensualizado')->after('modo_decimo_cuarto');
            }
        });

        // Migrar los toggles antiguos al nuevo esquema (mejor esfuerzo).
        if (Schema::hasColumn('empleados', 'decimos_mensualizados')) {
            DB::table('empleados')->where('decimos_mensualizados', true)->update([
                'modo_decimo_tercero' => 'mensualizado',
                'modo_decimo_cuarto' => 'mensualizado',
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $t) {
            foreach (['region', 'modo_decimo_tercero', 'modo_decimo_cuarto', 'modo_fondos_reserva'] as $c) {
                if (Schema::hasColumn('empleados', $c)) $t->dropColumn($c);
            }
        });
    }
};
