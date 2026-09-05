<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recibos', function (Blueprint $table) {
            if (! Schema::hasColumn('recibos', 'cheque_cobrado')) {
                $table->boolean('cheque_cobrado')->default(false)->after('cheque_fecha_cobro');
            }
            if (! Schema::hasColumn('recibos', 'cheque_cobrado_at')) {
                $table->timestamp('cheque_cobrado_at')->nullable()->after('cheque_cobrado');
            }
        });
    }

    public function down(): void
    {
        Schema::table('recibos', function (Blueprint $table) {
            $table->dropColumn(['cheque_cobrado', 'cheque_cobrado_at']);
        });
    }
};
