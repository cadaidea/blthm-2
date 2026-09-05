<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('articulos') && ! Schema::hasColumn('articulos', 'contenido_json')) {
            Schema::table('articulos', function (Blueprint $t) {
                $t->longText('contenido_json')->nullable()->after('contenido');
            });
        }
        if (Schema::hasTable('productos') && ! Schema::hasColumn('productos', 'descripcion_json')) {
            Schema::table('productos', function (Blueprint $t) {
                $t->longText('descripcion_json')->nullable()->after('descripcion');
            });
        }
        if (Schema::hasTable('paginas') && ! Schema::hasColumn('paginas', 'contenido_json')) {
            Schema::table('paginas', function (Blueprint $t) {
                $t->longText('contenido_json')->nullable()->after('contenido');
            });
        }
    }

    public function down(): void
    {
        foreach ([['articulos', 'contenido_json'], ['productos', 'descripcion_json'], ['paginas', 'contenido_json']] as [$tabla, $col]) {
            if (Schema::hasTable($tabla) && Schema::hasColumn($tabla, $col)) {
                Schema::table($tabla, fn (Blueprint $t) => $t->dropColumn($col));
            }
        }
    }
};
