<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('despachos', function (Blueprint $table) {
            if (! Schema::hasColumn('despachos', 'detalle_json')) {
                $table->text('detalle_json')->nullable()->after('notas');
            }
        });
    }
    public function down(): void
    {
        Schema::table('despachos', function (Blueprint $table) {
            $table->dropColumn('detalle_json');
        });
    }
};
