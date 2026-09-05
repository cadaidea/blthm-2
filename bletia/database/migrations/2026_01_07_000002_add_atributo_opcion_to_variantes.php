<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('variantes', function (Blueprint $t) {
        if (!Schema::hasColumn('variantes','atributo_opcion_id')) $t->foreignId('atributo_opcion_id')->nullable()->after('producto_id')->constrained('atributo_opciones')->nullOnDelete();
    }); }
    public function down(): void { Schema::table('variantes', fn (Blueprint $t) => $t->dropConstrainedForeignId('atributo_opcion_id')); }
};
