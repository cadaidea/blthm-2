<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $t) {
            if (! Schema::hasColumn('ventas', 'es_credito')) $t->boolean('es_credito')->default(false)->after('info_adicional');
            if (! Schema::hasColumn('ventas', 'credito_plazo_dias')) $t->integer('credito_plazo_dias')->nullable()->after('es_credito');
            if (! Schema::hasColumn('ventas', 'credito_vence_at')) $t->date('credito_vence_at')->nullable()->after('credito_plazo_dias');
            if (! Schema::hasColumn('ventas', 'saldo_credito')) $t->decimal('saldo_credito', 12, 2)->default(0)->after('credito_vence_at');
        });
    }
    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $t) {
            foreach (['es_credito', 'credito_plazo_dias', 'credito_vence_at', 'saldo_credito'] as $c) {
                if (Schema::hasColumn('ventas', $c)) $t->dropColumn($c);
            }
        });
    }
};
