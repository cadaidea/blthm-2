<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $t) {
            if (! Schema::hasColumn('ventas', 'info_adicional')) {
                $t->text('info_adicional')->nullable()->after('codigo_origen');
            }
        });
    }
    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $t) {
            if (Schema::hasColumn('ventas', 'info_adicional')) $t->dropColumn('info_adicional');
        });
    }
};
