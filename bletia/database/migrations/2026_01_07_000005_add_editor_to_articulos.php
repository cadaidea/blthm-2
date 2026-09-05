<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('articulos', function (Blueprint $t) {
        if (!Schema::hasColumn('articulos','editor_id')) $t->foreignId('editor_id')->nullable()->after('blog_categoria_id')->constrained('editores')->nullOnDelete();
    }); }
    public function down(): void { Schema::table('articulos', fn (Blueprint $t) => $t->dropConstrainedForeignId('editor_id')); }
};
