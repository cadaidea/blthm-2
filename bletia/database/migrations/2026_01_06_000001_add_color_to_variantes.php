<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('variantes', function (Blueprint $t) {
        if (!Schema::hasColumn('variantes','color')) $t->string('color', 20)->nullable()->after('valor');
    }); }
    public function down(): void { Schema::table('variantes', fn (Blueprint $t) => $t->dropColumn('color')); }
};
