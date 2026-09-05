<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('despachos', function (Blueprint $table) {
            if (! Schema::hasColumn('despachos', 'reclamo_id'))
                $table->unsignedBigInteger('reclamo_id')->nullable()->after('pedido_id');
            if (! Schema::hasColumn('despachos', 'tipo'))
                $table->string('tipo', 20)->default('normal')->after('reclamo_id'); // normal|garantia
        });
    }
    public function down(): void {
        Schema::table('despachos', function (Blueprint $table) {
            $table->dropColumn(['reclamo_id', 'tipo']);
        });
    }
};
