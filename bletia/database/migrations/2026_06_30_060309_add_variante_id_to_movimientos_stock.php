<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('movimientos_stock', function (Blueprint $table) {
            if (! Schema::hasColumn('movimientos_stock', 'variante_id')) {
                $table->unsignedBigInteger('variante_id')->nullable()->after('producto_id');
            }
        });
    }
    public function down(): void {
        Schema::table('movimientos_stock', fn ($t) => $t->dropColumn('variante_id'));
    }
};
