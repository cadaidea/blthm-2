<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ---- PROVEEDORES: identificación (RUC/cédula/pasaporte) ----
        Schema::table('proveedores', function (Blueprint $t) {
            if (! Schema::hasColumn('proveedores', 'identificacion')) {
                $t->string('identificacion', 20)->nullable()->after('nombre');
            }
            if (! Schema::hasColumn('proveedores', 'tipo_identificacion')) {
                // ruc | cedula | pasaporte
                $t->string('tipo_identificacion', 12)->nullable()->after('identificacion');
            }
        });

        // ---- COMPRAS: sustento tributario + retenciones (agente de retención) ----
        Schema::table('compras', function (Blueprint $t) {
            if (! Schema::hasColumn('compras', 'sustento_tributario')) {
                // código ATS de sustento (01 crédito tributario, 02 costo/gasto, etc.)
                $t->string('sustento_tributario', 4)->nullable()->after('doc_fecha');
            }
            if (! Schema::hasColumn('compras', 'autorizacion_sri')) {
                $t->string('autorizacion_sri', 60)->nullable()->after('sustento_tributario');
            }
            if (! Schema::hasColumn('compras', 'ret_iva')) {
                $t->decimal('ret_iva', 12, 2)->default(0)->after('total');
            }
            if (! Schema::hasColumn('compras', 'ret_renta')) {
                $t->decimal('ret_renta', 12, 2)->default(0)->after('ret_iva');
            }
            if (! Schema::hasColumn('compras', 'ret_comprobante')) {
                $t->string('ret_comprobante', 30)->nullable()->after('ret_renta');
            }
            if (! Schema::hasColumn('compras', 'ret_fecha')) {
                $t->date('ret_fecha')->nullable()->after('ret_comprobante');
            }
        });

        // ---- VENTAS: retenciones que te aplica el cliente ----
        Schema::table('ventas', function (Blueprint $t) {
            if (! Schema::hasColumn('ventas', 'ret_iva')) {
                $t->decimal('ret_iva', 12, 2)->default(0)->after('total');
            }
            if (! Schema::hasColumn('ventas', 'ret_renta')) {
                $t->decimal('ret_renta', 12, 2)->default(0)->after('ret_iva');
            }
            if (! Schema::hasColumn('ventas', 'ret_comprobante')) {
                $t->string('ret_comprobante', 30)->nullable()->after('ret_renta');
            }
            if (! Schema::hasColumn('ventas', 'ret_fecha')) {
                $t->date('ret_fecha')->nullable()->after('ret_comprobante');
            }
        });
    }

    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $t) {
            foreach (['identificacion', 'tipo_identificacion'] as $c) {
                if (Schema::hasColumn('proveedores', $c)) $t->dropColumn($c);
            }
        });
        Schema::table('compras', function (Blueprint $t) {
            foreach (['sustento_tributario', 'autorizacion_sri', 'ret_iva', 'ret_renta', 'ret_comprobante', 'ret_fecha'] as $c) {
                if (Schema::hasColumn('compras', $c)) $t->dropColumn($c);
            }
        });
        Schema::table('ventas', function (Blueprint $t) {
            foreach (['ret_iva', 'ret_renta', 'ret_comprobante', 'ret_fecha'] as $c) {
                if (Schema::hasColumn('ventas', $c)) $t->dropColumn($c);
            }
        });
    }
};
