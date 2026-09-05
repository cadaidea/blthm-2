<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recibos', function (Blueprint $t) {
            if (! Schema::hasColumn('recibos', 'tarjeta_naturaleza')) {
                $t->string('tarjeta_naturaleza', 10)->nullable()->after('tipo_tarjeta'); // debito | credito
            }
        });
    }
    public function down(): void
    {
        Schema::table('recibos', function (Blueprint $t) {
            if (Schema::hasColumn('recibos', 'tarjeta_naturaleza')) $t->dropColumn('tarjeta_naturaleza');
        });
    }
};
