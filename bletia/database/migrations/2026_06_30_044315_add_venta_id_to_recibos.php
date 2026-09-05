<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recibos', function (Blueprint $table) {
            if (! Schema::hasColumn('recibos', 'venta_id')) {
                $table->unsignedBigInteger('venta_id')->nullable()->after('pedido_id');
            }
        });
        // pedido_id pasa a ser opcional (venta directa no tiene pedido)
        DB::statement('ALTER TABLE recibos MODIFY pedido_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        Schema::table('recibos', function (Blueprint $table) {
            $table->dropColumn('venta_id');
        });
        DB::statement('ALTER TABLE recibos MODIFY pedido_id BIGINT UNSIGNED NOT NULL');
    }
};
