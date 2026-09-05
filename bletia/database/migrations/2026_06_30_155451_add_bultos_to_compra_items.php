<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('compra_items', function (Blueprint $table) {
            if (! Schema::hasColumn('compra_items', 'bultos')) {
                $table->unsignedSmallInteger('bultos')->default(1)->after('cantidad');
            }
        });
    }
    public function down(): void {
        Schema::table('compra_items', fn ($t) => $t->dropColumn('bultos'));
    }
};
