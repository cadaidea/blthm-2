<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Columnas nuevas en liquidaciones
        Schema::table('liquidaciones', function (Blueprint $t) {
            if (! Schema::hasColumn('liquidaciones', 'indemnizacion')) {
                $t->decimal('indemnizacion', 12, 2)->default(0)->after('fondos_reserva');
            }
            if (! Schema::hasColumn('liquidaciones', 'bonificacion_desahucio')) {
                $t->decimal('bonificacion_desahucio', 12, 2)->default(0)->after('indemnizacion');
            }
            if (! Schema::hasColumn('liquidaciones', 'anios_servicio')) {
                $t->unsignedSmallInteger('anios_servicio')->nullable()->after('bonificacion_desahucio');
            }
            if (! Schema::hasColumn('liquidaciones', 'mejor_remuneracion')) {
                $t->decimal('mejor_remuneracion', 12, 2)->nullable()->after('anios_servicio');
            }
        });

        // Cuenta 6.1.12 Indemnizaciones laborales (mismo padre que 6.1.11 Gastos varios)
        if (! DB::table('cuentas')->where('codigo', '6.1.12')->exists()) {
            $padre = DB::table('cuentas')->where('codigo', '6.1.11')->value('padre_id');
            DB::table('cuentas')->insert([
                'codigo' => '6.1.12', 'nombre' => 'Indemnizaciones laborales',
                'tipo' => 'gasto', 'naturaleza' => 'deudora',
                'padre_id' => $padre, 'es_movimiento' => true, 'activo' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // Mapeo contable
        if (Schema::hasTable('cuenta_mapeos') && ! DB::table('cuenta_mapeos')->where('clave', 'liquidacion.indemnizacion_gasto')->exists()) {
            DB::table('cuenta_mapeos')->insert([
                'clave' => 'liquidacion.indemnizacion_gasto',
                'descripcion' => 'Liquidación · gasto por indemnización/desahucio',
                'codigo_cuenta' => '6.1.12',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('liquidaciones', function (Blueprint $t) {
            foreach (['indemnizacion', 'bonificacion_desahucio', 'anios_servicio', 'mejor_remuneracion'] as $c) {
                if (Schema::hasColumn('liquidaciones', $c)) $t->dropColumn($c);
            }
        });
    }
};
