<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Vínculos opcionales: la ficha de Empleado es la fuente única de la persona.
        Schema::table('empleados', function (Blueprint $t) {
            if (! Schema::hasColumn('empleados', 'user_id')) {
                $t->unsignedBigInteger('user_id')->nullable()->after('id');
                $t->index('user_id');
            }
            if (! Schema::hasColumn('empleados', 'editor_id')) {
                $t->unsignedBigInteger('editor_id')->nullable()->after('user_id');
                $t->index('editor_id');
            }
        });

        // Incentivos a colaboradores (sin relación de dependencia).
        if (! Schema::hasTable('incentivos')) {
            Schema::create('incentivos', function (Blueprint $t) {
                $t->id();
                $t->string('folio', 20)->nullable();
                $t->foreignId('empleado_id')->constrained('empleados');
                $t->date('fecha');
                $t->string('concepto', 160);
                $t->decimal('monto', 12, 2);
                $t->decimal('ret_renta', 12, 2)->default(0);
                $t->decimal('total', 12, 2)->default(0);
                $t->string('metodo_pago', 20)->nullable();
                $t->string('nro_comprobante', 40)->nullable();
                $t->string('adjunto')->nullable();
                $t->text('nota')->nullable();
                $t->string('estado', 12)->default('pagado'); // pagado | anulado
                $t->unsignedBigInteger('creado_por')->nullable();
                $t->timestamps();
                $t->index(['empleado_id', 'estado']);
            });
        }

        // Cuenta contable para incentivos (6.1.13), colgada del mismo padre que 6.1.11.
        if (! DB::table('cuentas')->where('codigo', '6.1.13')->exists()) {
            $padre = DB::table('cuentas')->where('codigo', '6.1.11')->value('padre_id');
            DB::table('cuentas')->insert([
                'codigo' => '6.1.13', 'nombre' => 'Incentivos a colaboradores',
                'tipo' => 'gasto', 'naturaleza' => 'deudora',
                'padre_id' => $padre, 'es_movimiento' => true, 'activo' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        if (Schema::hasTable('cuenta_mapeos')) {
            $nuevos = [
                ['incentivo.gasto', 'Incentivo a colaborador · gasto', '6.1.13'],
                ['incentivo.banco', 'Incentivo · salida de banco', '1.1.01.03'],
                ['incentivo.caja', 'Incentivo · salida de caja', '1.1.01.01'],
            ];
            foreach ($nuevos as [$c, $d, $cod]) {
                if (! DB::table('cuenta_mapeos')->where('clave', $c)->exists()) {
                    DB::table('cuenta_mapeos')->insert(['clave' => $c, 'descripcion' => $d, 'codigo_cuenta' => $cod, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('incentivos');
        Schema::table('empleados', function (Blueprint $t) {
            foreach (['user_id', 'editor_id'] as $c) {
                if (Schema::hasColumn('empleados', $c)) $t->dropColumn($c);
            }
        });
    }
};
