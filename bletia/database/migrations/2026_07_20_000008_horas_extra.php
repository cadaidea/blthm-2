<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('roles_pago', function (Blueprint $t) {
            if (! Schema::hasColumn('roles_pago', 'horas_suplementarias')) {
                $t->decimal('horas_suplementarias', 8, 2)->default(0)->after('horas_extra');
            }
            if (! Schema::hasColumn('roles_pago', 'horas_extraordinarias')) {
                $t->decimal('horas_extraordinarias', 8, 2)->default(0)->after('horas_suplementarias');
            }
        });
    }

    public function down(): void
    {
        Schema::table('roles_pago', function (Blueprint $t) {
            foreach (['horas_suplementarias', 'horas_extraordinarias'] as $c) {
                if (Schema::hasColumn('roles_pago', $c)) $t->dropColumn($c);
            }
        });
    }
};
