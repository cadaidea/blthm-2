<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('liquidaciones', function (Blueprint $t) {
            if (! Schema::hasColumn('liquidaciones', 'tiempo_servicio')) {
                $t->string('tiempo_servicio', 60)->nullable()->after('mejor_remuneracion');
            }
        });
    }
    public function down(): void
    {
        Schema::table('liquidaciones', function (Blueprint $t) {
            if (Schema::hasColumn('liquidaciones', 'tiempo_servicio')) $t->dropColumn('tiempo_servicio');
        });
    }
};
