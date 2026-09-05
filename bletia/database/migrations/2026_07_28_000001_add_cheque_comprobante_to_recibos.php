<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recibos', function (Blueprint $t) {
            if (! Schema::hasColumn('recibos', 'cheque_foto_comprobante')) {
                $t->string('cheque_foto_comprobante')->nullable()->after('cheque_reemplazo_id');
            }
            if (! Schema::hasColumn('recibos', 'cheque_num_deposito')) {
                $t->string('cheque_num_deposito', 60)->nullable()->after('cheque_foto_comprobante');
            }
            if (! Schema::hasColumn('recibos', 'cheque_sustento_sri')) {
                $t->string('cheque_sustento_sri', 10)->nullable()->after('cheque_num_deposito');
            }
        });
    }
    public function down(): void
    {
        Schema::table('recibos', function (Blueprint $t) {
            foreach (['cheque_foto_comprobante', 'cheque_num_deposito', 'cheque_sustento_sri'] as $c) {
                if (Schema::hasColumn('recibos', $c)) $t->dropColumn($c);
            }
        });
    }
};
