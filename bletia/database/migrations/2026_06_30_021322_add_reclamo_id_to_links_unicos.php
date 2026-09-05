<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('links_unicos', function (Blueprint $table) {
            if (! Schema::hasColumn('links_unicos', 'reclamo_id'))
                $table->unsignedBigInteger('reclamo_id')->nullable()->after('despacho_id');
        });
        // Campo bultos en reclamos
        Schema::table('reclamos', function (Blueprint $table) {
            if (! Schema::hasColumn('reclamos', 'bultos'))
                $table->unsignedSmallInteger('bultos')->default(1)->after('fotos');
        });
    }
    public function down(): void {
        Schema::table('links_unicos', fn ($t) => $t->dropColumn('reclamo_id'));
        Schema::table('reclamos', fn ($t) => $t->dropColumn('bultos'));
    }
};
