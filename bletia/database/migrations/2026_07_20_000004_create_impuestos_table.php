<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('impuestos')) {
            Schema::create('impuestos', function (Blueprint $t) {
                $t->id();
                $t->string('nombre', 60);              // "IVA", "Retención IVA 30%", etc.
                $t->string('tipo', 20)->default('iva'); // iva | retencion_iva | retencion_renta | ice
                $t->decimal('porcentaje', 6, 2);        // 15.00
                $t->string('codigo_sri', 10)->nullable();
                $t->date('vigente_desde');
                $t->date('vigente_hasta')->nullable();  // null = vigente actual
                $t->boolean('activo')->default(true);
                $t->timestamps();
                $t->index(['tipo', 'vigente_desde']);
            });
        }

        // Seed con historial real de IVA en Ecuador
        if (DB::table('impuestos')->where('tipo', 'iva')->count() === 0) {
            DB::table('impuestos')->insert([
                [
                    'nombre' => 'IVA', 'tipo' => 'iva', 'porcentaje' => 12.00, 'codigo_sri' => '2',
                    'vigente_desde' => '2000-01-01', 'vigente_hasta' => '2024-03-31',
                    'activo' => true, 'created_at' => now(), 'updated_at' => now(),
                ],
                [
                    'nombre' => 'IVA', 'tipo' => 'iva', 'porcentaje' => 15.00, 'codigo_sri' => '4',
                    'vigente_desde' => '2024-04-01', 'vigente_hasta' => null,
                    'activo' => true, 'created_at' => now(), 'updated_at' => now(),
                ],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('impuestos');
    }
};
