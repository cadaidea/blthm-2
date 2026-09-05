<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $t) {
            if (! Schema::hasColumn('ventas', 'tipo_comprobante')) {
                // factura | nota_venta
                $t->string('tipo_comprobante', 15)->nullable()->after('folio');
            }
            if (! Schema::hasColumn('ventas', 'numero_comprobante')) {
                // número visible: SRI "001-001-000000008" o "VEN-000005"
                $t->string('numero_comprobante', 40)->nullable()->after('tipo_comprobante');
            }
            if (! Schema::hasColumn('ventas', 'sri_comprobante_id')) {
                // enlace al comprobante SRI cuando es factura
                $t->unsignedBigInteger('sri_comprobante_id')->nullable()->after('numero_comprobante');
            }
            if (! Schema::hasColumn('ventas', 'origen')) {
                $t->string('origen', 20)->nullable()->after('forma_venta');
            }
            if (! Schema::hasColumn('ventas', 'codigo_origen')) {
                $t->string('codigo_origen', 60)->nullable()->after('origen');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $t) {
            foreach (['tipo_comprobante', 'numero_comprobante', 'sri_comprobante_id', 'origen', 'codigo_origen'] as $c) {
                if (Schema::hasColumn('ventas', $c)) $t->dropColumn($c);
            }
        });
    }
};
