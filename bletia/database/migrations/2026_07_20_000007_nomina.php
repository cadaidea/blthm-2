<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ---- EMPLEADOS ----
        if (! Schema::hasTable('empleados')) {
            Schema::create('empleados', function (Blueprint $t) {
                $t->id();
                $t->string('nombre', 160);
                $t->string('identificacion', 20)->nullable();
                $t->string('tipo_identificacion', 12)->default('cedula');
                $t->string('cargo', 120)->nullable();
                $t->string('email', 160)->nullable();
                $t->string('telefono', 30)->nullable();
                $t->string('direccion', 200)->nullable();
                // relacion: dependencia (rol IESS) | honorarios (factura, sin IESS)
                $t->string('relacion', 20)->default('dependencia');
                $t->date('fecha_ingreso')->nullable();
                $t->date('fecha_salida')->nullable();
                $t->decimal('sueldo', 12, 2)->default(0);      // sueldo/honorario base mensual
                $t->string('banco', 80)->nullable();
                $t->string('cuenta_bancaria', 40)->nullable();
                $t->string('tipo_cuenta', 20)->nullable();     // ahorros | corriente
                $t->integer('cargas_familiares')->default(0);
                $t->boolean('recibe_fondos_reserva')->default(false); // desde 2º año
                $t->boolean('decimos_mensualizados')->default(false);
                $t->boolean('activo')->default(true);
                $t->text('notas')->nullable();
                $t->timestamps();
                $t->index(['relacion', 'activo']);
            });
        }

        // ---- ROL DE PAGOS (cabecera por empleado/periodo) ----
        if (! Schema::hasTable('roles_pago')) {
            Schema::create('roles_pago', function (Blueprint $t) {
                $t->id();
                $t->string('folio', 20)->nullable();
                $t->foreignId('empleado_id')->constrained('empleados');
                $t->unsignedSmallInteger('anio');
                $t->unsignedTinyInteger('mes');
                $t->string('relacion', 20)->default('dependencia');
                // Ingresos
                $t->decimal('sueldo', 12, 2)->default(0);
                $t->decimal('horas_extra', 12, 2)->default(0);
                $t->decimal('comisiones', 12, 2)->default(0);
                $t->decimal('bonos', 12, 2)->default(0);
                $t->decimal('otros_ingresos', 12, 2)->default(0);
                $t->decimal('total_ingresos', 12, 2)->default(0);
                // Descuentos (dependencia)
                $t->decimal('aporte_personal', 12, 2)->default(0);   // 9.45%
                $t->decimal('anticipos', 12, 2)->default(0);
                $t->decimal('prestamos_iess', 12, 2)->default(0);
                $t->decimal('otros_descuentos', 12, 2)->default(0);
                $t->decimal('ret_renta', 12, 2)->default(0);         // honorarios / relación dependencia con IR
                $t->decimal('total_descuentos', 12, 2)->default(0);
                // Provisiones patronales (no se descuentan al empleado)
                $t->decimal('aporte_patronal', 12, 2)->default(0);   // 11.15%
                $t->decimal('decimo_tercero', 12, 2)->default(0);    // 1/12
                $t->decimal('decimo_cuarto', 12, 2)->default(0);     // SBU/12
                $t->decimal('vacaciones', 12, 2)->default(0);        // 1/24
                $t->decimal('fondos_reserva', 12, 2)->default(0);    // 8.33%
                // Neto
                $t->decimal('liquido', 12, 2)->default(0);           // total_ingresos - total_descuentos
                $t->decimal('costo_empresa', 12, 2)->default(0);     // ingresos + provisiones patronales
                $t->string('estado', 12)->default('borrador');       // borrador | pagado | anulado
                $t->date('fecha_pago')->nullable();
                $t->string('metodo_pago', 20)->nullable();
                $t->unsignedBigInteger('creado_por')->nullable();
                $t->timestamps();
                $t->unique(['empleado_id', 'anio', 'mes']);
                $t->index(['anio', 'mes']);
            });
        }

        // ---- PARÁMETROS LABORALES (editables por año) ----
        if (! Schema::hasTable('parametros_laborales')) {
            Schema::create('parametros_laborales', function (Blueprint $t) {
                $t->id();
                $t->unsignedSmallInteger('anio')->unique();
                $t->decimal('sbu', 10, 2);              // salario básico
                $t->decimal('aporte_personal', 6, 2);   // 9.45
                $t->decimal('aporte_patronal', 6, 2);   // 11.15
                $t->decimal('fondos_reserva', 6, 2);    // 8.33
                $t->timestamps();
            });
            DB::table('parametros_laborales')->insert([
                ['anio' => 2025, 'sbu' => 470.00, 'aporte_personal' => 9.45, 'aporte_patronal' => 11.15, 'fondos_reserva' => 8.33, 'created_at' => now(), 'updated_at' => now()],
                ['anio' => 2026, 'sbu' => 482.00, 'aporte_personal' => 9.45, 'aporte_patronal' => 11.15, 'fondos_reserva' => 8.33, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // mapeos contables de nómina
        if (Schema::hasTable('cuenta_mapeos')) {
            $nuevos = [
                ['nomina.sueldo_gasto', 'Nómina · gasto sueldos', '6.1.01'],
                ['nomina.aporte_patronal_gasto', 'Nómina · gasto aporte patronal', '6.1.03'],
                ['nomina.beneficios_gasto', 'Nómina · gasto beneficios sociales', '6.1.02'],
                ['nomina.liquido_pagar', 'Nómina · sueldos por pagar', '2.1.03.01'],
                ['nomina.iess_pagar', 'Nómina · IESS por pagar', '2.1.03.02'],
                ['nomina.beneficios_pagar', 'Nómina · beneficios por pagar', '2.1.03.03'],
                ['nomina.ret_renta_pagar', 'Nómina · retención renta por pagar', '2.1.02.04'],
                ['nomina.honorarios_gasto', 'Honorarios · gasto', '6.1.01'],
            ];
            foreach ($nuevos as [$clave, $desc, $cod]) {
                if (! DB::table('cuenta_mapeos')->where('clave', $clave)->exists()) {
                    DB::table('cuenta_mapeos')->insert(['clave' => $clave, 'descripcion' => $desc, 'codigo_cuenta' => $cod, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('roles_pago');
        Schema::dropIfExists('empleados');
        Schema::dropIfExists('parametros_laborales');
    }
};
