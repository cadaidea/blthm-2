<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('paginas', function (Blueprint $t) {
        if (!Schema::hasColumn('paginas','bloques')) $t->json('bloques')->nullable()->after('contenido');
    }); }
    public function down(): void { Schema::table('paginas', fn (Blueprint $t) => $t->dropColumn('bloques')); }
};
