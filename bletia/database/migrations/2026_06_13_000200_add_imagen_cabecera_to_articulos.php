<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('articulos') && ! Schema::hasColumn('articulos', 'imagen_cabecera')) {
            Schema::table('articulos', function (Blueprint $table) {
                $table->boolean('imagen_cabecera')->default(false)->after('imagen');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('articulos') && Schema::hasColumn('articulos', 'imagen_cabecera')) {
            Schema::table('articulos', function (Blueprint $table) {
                $table->dropColumn('imagen_cabecera');
            });
        }
    }
};
