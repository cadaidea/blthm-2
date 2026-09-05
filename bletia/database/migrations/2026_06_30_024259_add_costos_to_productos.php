<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('productos', function (Blueprint $table) {
            if (! Schema::hasColumn('productos', 'costo_produccion'))
                $table->decimal('costo_produccion', 12, 2)->nullable()->after('precio');
            if (! Schema::hasColumn('productos', 'costo_proveedor'))
                $table->decimal('costo_proveedor', 12, 2)->nullable()->after('costo_produccion');
        });
    }
    public function down(): void {
        Schema::table('productos', fn ($t) => $t->dropColumn(['costo_produccion', 'costo_proveedor']));
    }
};
