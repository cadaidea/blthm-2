<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('despachos', function (Blueprint $table) {
            if (! Schema::hasColumn('despachos', 'venta_id')) {
                $table->unsignedBigInteger('venta_id')->nullable()->after('compra_id');
            }
        });
    }
    public function down(): void
    {
        Schema::table('despachos', function (Blueprint $table) {
            $table->dropColumn('venta_id');
        });
    }
};
