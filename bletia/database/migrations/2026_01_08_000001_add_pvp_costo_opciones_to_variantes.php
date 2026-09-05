<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('variantes', function (Blueprint $t) {
        if (!Schema::hasColumn('variantes','pvp'))      $t->decimal('pvp', 12, 2)->nullable()->after('precio_extra');
        if (!Schema::hasColumn('variantes','costo'))    $t->decimal('costo', 12, 2)->nullable()->after('pvp');
        if (!Schema::hasColumn('variantes','opciones')) $t->json('opciones')->nullable()->after('costo');
    }); }
    public function down(): void { Schema::table('variantes', fn (Blueprint $t) => $t->dropColumn(['pvp','costo','opciones'])); }
};
