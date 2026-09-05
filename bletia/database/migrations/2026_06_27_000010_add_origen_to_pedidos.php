<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $t) {
            if (! Schema::hasColumn('pedidos', 'origen')) {
                // local | woo | tienda | (null = desconocido/histórico)
                $t->string('origen', 20)->nullable()->after('tipo_erp');
            }
            if (! Schema::hasColumn('pedidos', 'codigo_origen')) {
                // código del canal de origen (ej. woo #882, web #1045, o folio legado VL-000007)
                $t->string('codigo_origen', 60)->nullable()->after('origen');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $t) {
            if (Schema::hasColumn('pedidos', 'codigo_origen')) $t->dropColumn('codigo_origen');
            if (Schema::hasColumn('pedidos', 'origen')) $t->dropColumn('origen');
        });
    }
};
