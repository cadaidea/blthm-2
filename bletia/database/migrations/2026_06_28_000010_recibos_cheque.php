<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recibos', function (Blueprint $t) {
            if (! Schema::hasColumn('recibos', 'cheque_girador')) $t->string('cheque_girador')->nullable()->after('tipo_tarjeta');
            if (! Schema::hasColumn('recibos', 'cheque_numero')) $t->string('cheque_numero', 60)->nullable()->after('cheque_girador');
            if (! Schema::hasColumn('recibos', 'cheque_banco')) $t->string('cheque_banco')->nullable()->after('cheque_numero');
            if (! Schema::hasColumn('recibos', 'cheque_fecha_cobro')) $t->date('cheque_fecha_cobro')->nullable()->after('cheque_banco');
        });
    }
    public function down(): void
    {
        Schema::table('recibos', function (Blueprint $t) {
            foreach (['cheque_girador', 'cheque_numero', 'cheque_banco', 'cheque_fecha_cobro'] as $c) {
                if (Schema::hasColumn('recibos', $c)) $t->dropColumn($c);
            }
        });
    }
};
