<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('movimientos_material', function (Blueprint $table) {
            if (! Schema::hasColumn('movimientos_material', 'compra_id')) {
                $table->unsignedBigInteger('compra_id')->nullable()->after('pedido_id');
            }
        });
    }
    public function down(): void {
        Schema::table('movimientos_material', fn ($t) => $t->dropColumn('compra_id'));
    }
};
