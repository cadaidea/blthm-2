<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pedidos')) return;

        Schema::table('pedidos', function (Blueprint $t) {
            if (! Schema::hasColumn('pedidos', 'observacion_anulacion')) {
                $t->text('observacion_anulacion')->nullable();
            }
            if (! Schema::hasColumn('pedidos', 'anulado_at')) {
                $t->timestamp('anulado_at')->nullable();
            }
        });

        // Los pedidos nuevos entran como "pendiente" (para revisar antes de fabricar).
        try { DB::statement("ALTER TABLE pedidos ALTER COLUMN estado_erp SET DEFAULT 'pendiente'"); }
        catch (\Throwable $e) { try { DB::statement("ALTER TABLE pedidos MODIFY estado_erp VARCHAR(40) DEFAULT 'pendiente'"); } catch (\Throwable $e2) {} }
    }

    public function down(): void {}
};
