<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('roles_pago', function (Blueprint $t) {
            if (! Schema::hasColumn('roles_pago', 'nro_comprobante_pago')) {
                $t->string('nro_comprobante_pago', 40)->nullable()->after('metodo_pago');
            }
            if (! Schema::hasColumn('roles_pago', 'banco_pago')) {
                $t->string('banco_pago', 80)->nullable()->after('nro_comprobante_pago');
            }
            if (! Schema::hasColumn('roles_pago', 'adjunto_pago')) {
                $t->string('adjunto_pago')->nullable()->after('banco_pago');
            }
            if (! Schema::hasColumn('roles_pago', 'nota_pago')) {
                $t->text('nota_pago')->nullable()->after('adjunto_pago');
            }
        });
    }

    public function down(): void
    {
        Schema::table('roles_pago', function (Blueprint $t) {
            foreach (['nro_comprobante_pago', 'banco_pago', 'adjunto_pago', 'nota_pago'] as $c) {
                if (Schema::hasColumn('roles_pago', $c)) $t->dropColumn($c);
            }
        });
    }
};
