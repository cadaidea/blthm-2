<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('articulos', function (Blueprint $t) {
        if (!Schema::hasColumn('articulos','bloques')) $t->json('bloques')->nullable()->after('contenido');
    }); }
    public function down(): void { Schema::table('articulos', fn (Blueprint $t) => $t->dropColumn('bloques')); }
};
