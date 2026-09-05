<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recibos', function (Blueprint $table) {
            if (! Schema::hasColumn('recibos', 'cheque_estado')) {
                // pendiente | cobrado | rechazado | anulado
                $table->string('cheque_estado', 20)->default('pendiente')->after('cheque_cobrado_at');
            }
            if (! Schema::hasColumn('recibos', 'cheque_motivo')) {
                $table->string('cheque_motivo', 255)->nullable()->after('cheque_estado');
            }
            if (! Schema::hasColumn('recibos', 'cheque_reemplazo_id')) {
                // si este cheque fue reemplazado, apunta al recibo del cheque nuevo
                $table->unsignedBigInteger('cheque_reemplazo_id')->nullable()->after('cheque_motivo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('recibos', function (Blueprint $table) {
            $table->dropColumn(['cheque_estado', 'cheque_motivo', 'cheque_reemplazo_id']);
        });
    }
};
