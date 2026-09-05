<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('despachos', function (Blueprint $table) {
            if (! Schema::hasColumn('despachos', 'empleado_receptor_id')) {
                $table->unsignedBigInteger('empleado_receptor_id')->nullable()->after('local_retiro_id');
            }
            if (! Schema::hasColumn('despachos', 'local_destino_id')) {
                $table->unsignedBigInteger('local_destino_id')->nullable()->after('empleado_receptor_id');
            }
        });
    }
    public function down(): void
    {
        Schema::table('despachos', function (Blueprint $table) {
            $table->dropColumn(['empleado_receptor_id', 'local_destino_id']);
        });
    }
};
