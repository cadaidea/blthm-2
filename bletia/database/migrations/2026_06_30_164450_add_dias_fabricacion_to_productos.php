<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('productos', function (Blueprint $table) {
            if (! Schema::hasColumn('productos', 'dias_fabricacion')) {
                $table->unsignedSmallInteger('dias_fabricacion')->nullable()->after('bultos_default');
            }
        });
    }
    public function down(): void {
        Schema::table('productos', fn ($t) => $t->dropColumn('dias_fabricacion'));
    }
};
