<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ---- PLAN DE CUENTAS ----
        if (! Schema::hasTable('cuentas')) {
            Schema::create('cuentas', function (Blueprint $t) {
                $t->id();
                $t->string('codigo', 20)->unique();          // 1.1.01.01
                $t->string('nombre', 120);
                $t->enum('tipo', ['activo', 'pasivo', 'patrimonio', 'ingreso', 'gasto', 'costo']);
                $t->enum('naturaleza', ['deudora', 'acreedora']); // deudora: sube al debe
                $t->unsignedBigInteger('padre_id')->nullable();   // jerarquía
                $t->boolean('es_movimiento')->default(true);      // false = cuenta de grupo (no recibe asientos)
                $t->boolean('activo')->default(true);
                $t->timestamps();
                $t->index(['tipo', 'codigo']);
            });
        }

        // ---- ASIENTOS (cabecera) ----
        if (! Schema::hasTable('asientos')) {
            Schema::create('asientos', function (Blueprint $t) {
                $t->id();
                $t->string('numero', 20)->nullable();     // folio contable continuo
                $t->date('fecha');
                $t->string('glosa', 255);                 // descripción
                $t->string('origen', 40)->nullable();     // manual | venta | compra | cobro | pago | reverso
                $t->string('origen_tipo', 40)->nullable(); // Venta, Compra, Recibo...
                $t->unsignedBigInteger('origen_id')->nullable();
                $t->decimal('debe', 14, 2)->default(0);
                $t->decimal('haber', 14, 2)->default(0);
                $t->enum('estado', ['registrado', 'anulado'])->default('registrado');
                $t->unsignedBigInteger('reversa_id')->nullable(); // si es un reverso, apunta al original
                $t->unsignedBigInteger('creado_por')->nullable();
                $t->timestamps();
                $t->index(['fecha', 'estado']);
                $t->index(['origen_tipo', 'origen_id']);
            });
        }

        // ---- LÍNEAS DEL ASIENTO ----
        if (! Schema::hasTable('asiento_lineas')) {
            Schema::create('asiento_lineas', function (Blueprint $t) {
                $t->id();
                $t->foreignId('asiento_id')->constrained('asientos')->cascadeOnDelete();
                $t->foreignId('cuenta_id')->constrained('cuentas');
                $t->decimal('debe', 14, 2)->default(0);
                $t->decimal('haber', 14, 2)->default(0);
                $t->string('detalle', 255)->nullable();
                $t->timestamps();
                $t->index('cuenta_id');
            });
        }

        $this->seedPlan();
    }

    protected function seedPlan(): void
    {
        if (DB::table('cuentas')->count() > 0) return;

        // Plan de cuentas base — empresa comercial/manufacturera EC (inventario perpetuo).
        // naturaleza: deudora (activo, gasto, costo) sube al DEBE; acreedora (pasivo, patrimonio, ingreso) al HABER.
        $D = 'deudora'; $A = 'acreedora';
        $cuentas = [
            // código, nombre, tipo, naturaleza, es_movimiento
            ['1', 'ACTIVO', 'activo', $D, false],
            ['1.1', 'Activo corriente', 'activo', $D, false],
            ['1.1.01', 'Efectivo y equivalentes', 'activo', $D, false],
            ['1.1.01.01', 'Caja', 'activo', $D, true],
            ['1.1.01.02', 'Caja chica', 'activo', $D, true],
            ['1.1.01.03', 'Bancos', 'activo', $D, true],
            ['1.1.02', 'Cuentas por cobrar', 'activo', $D, false],
            ['1.1.02.01', 'Clientes', 'activo', $D, true],
            ['1.1.02.02', 'Cheques por cobrar', 'activo', $D, true],
            ['1.1.03', 'Crédito tributario', 'activo', $D, false],
            ['1.1.03.01', 'IVA en compras (crédito tributario)', 'activo', $D, true],
            ['1.1.03.02', 'Retención IVA cliente (a favor)', 'activo', $D, true],
            ['1.1.03.03', 'Retención Renta cliente (a favor)', 'activo', $D, true],
            ['1.1.04', 'Inventarios', 'activo', $D, false],
            ['1.1.04.01', 'Inventario de mercadería', 'activo', $D, true],
            ['1.1.04.02', 'Inventario de materia prima', 'activo', $D, true],
            ['1.1.04.03', 'Productos en proceso', 'activo', $D, true],
            ['1.1.04.04', 'Productos terminados', 'activo', $D, true],
            ['1.2', 'Activo no corriente', 'activo', $D, false],
            ['1.2.01', 'Propiedad, planta y equipo', 'activo', $D, false],
            ['1.2.01.01', 'Maquinaria y equipo', 'activo', $D, true],
            ['1.2.01.02', 'Muebles y enseres', 'activo', $D, true],
            ['1.2.01.03', 'Vehículos', 'activo', $D, true],
            ['1.2.02', 'Depreciación acumulada', 'activo', $A, true],

            ['2', 'PASIVO', 'pasivo', $A, false],
            ['2.1', 'Pasivo corriente', 'pasivo', $A, false],
            ['2.1.01', 'Cuentas por pagar', 'pasivo', $A, false],
            ['2.1.01.01', 'Proveedores', 'pasivo', $A, true],
            ['2.1.02', 'Obligaciones fiscales', 'pasivo', $A, false],
            ['2.1.02.01', 'IVA en ventas (por pagar)', 'pasivo', $A, true],
            ['2.1.02.02', 'IVA por pagar (neto)', 'pasivo', $A, true],
            ['2.1.02.03', 'Retención IVA por pagar', 'pasivo', $A, true],
            ['2.1.02.04', 'Retención Renta por pagar', 'pasivo', $A, true],
            ['2.1.02.05', 'Impuesto a la renta por pagar', 'pasivo', $A, true],
            ['2.1.03', 'Obligaciones con empleados (IESS/beneficios)', 'pasivo', $A, false],
            ['2.1.03.01', 'Sueldos por pagar', 'pasivo', $A, true],
            ['2.1.03.02', 'IESS por pagar', 'pasivo', $A, true],
            ['2.1.03.03', 'Beneficios sociales por pagar', 'pasivo', $A, true],
            ['2.2', 'Pasivo no corriente', 'pasivo', $A, false],
            ['2.2.01', 'Préstamos bancarios largo plazo', 'pasivo', $A, true],

            ['3', 'PATRIMONIO', 'patrimonio', $A, false],
            ['3.1', 'Capital', 'patrimonio', $A, false],
            ['3.1.01', 'Capital social', 'patrimonio', $A, true],
            ['3.2', 'Resultados', 'patrimonio', $A, false],
            ['3.2.01', 'Utilidad del ejercicio', 'patrimonio', $A, true],
            ['3.2.02', 'Utilidades acumuladas', 'patrimonio', $A, true],

            ['4', 'INGRESOS', 'ingreso', $A, false],
            ['4.1', 'Ingresos operacionales', 'ingreso', $A, false],
            ['4.1.01', 'Ventas de bienes', 'ingreso', $A, true],
            ['4.1.02', 'Ventas de servicios', 'ingreso', $A, true],
            ['4.1.03', 'Descuento en ventas', 'ingreso', $D, true],
            ['4.2', 'Otros ingresos', 'ingreso', $A, false],
            ['4.2.01', 'Otros ingresos', 'ingreso', $A, true],

            ['5', 'COSTOS', 'costo', $D, false],
            ['5.1', 'Costo de ventas', 'costo', $D, false],
            ['5.1.01', 'Costo de mercadería vendida', 'costo', $D, true],
            ['5.1.02', 'Costo de producción', 'costo', $D, true],

            ['6', 'GASTOS', 'gasto', $D, false],
            ['6.1', 'Gastos operacionales', 'gasto', $D, false],
            ['6.1.01', 'Sueldos y salarios', 'gasto', $D, true],
            ['6.1.02', 'Beneficios sociales', 'gasto', $D, true],
            ['6.1.03', 'Aporte patronal IESS', 'gasto', $D, true],
            ['6.1.04', 'Arriendo', 'gasto', $D, true],
            ['6.1.05', 'Servicios básicos', 'gasto', $D, true],
            ['6.1.06', 'Publicidad y marketing', 'gasto', $D, true],
            ['6.1.07', 'Transporte y flete', 'gasto', $D, true],
            ['6.1.08', 'Depreciación', 'gasto', $D, true],
            ['6.1.09', 'Suministros y materiales', 'gasto', $D, true],
            ['6.1.10', 'Comisiones bancarias', 'gasto', $D, true],
            ['6.1.11', 'Gastos varios', 'gasto', $D, true],
        ];

        // resolver padre por prefijo de código
        $ids = [];
        foreach ($cuentas as [$cod, $nom, $tipo, $nat, $mov]) {
            $padreId = null;
            $p = $cod;
            while (($pos = strrpos($p, '.')) !== false) {
                $p = substr($p, 0, $pos);
                if (isset($ids[$p])) { $padreId = $ids[$p]; break; }
            }
            $id = DB::table('cuentas')->insertGetId([
                'codigo' => $cod, 'nombre' => $nom, 'tipo' => $tipo, 'naturaleza' => $nat,
                'padre_id' => $padreId, 'es_movimiento' => $mov, 'activo' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $ids[$cod] = $id;
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('asiento_lineas');
        Schema::dropIfExists('asientos');
        Schema::dropIfExists('cuentas');
    }
};
