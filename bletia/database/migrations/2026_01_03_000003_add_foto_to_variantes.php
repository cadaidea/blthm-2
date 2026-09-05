<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('variantes', function (Blueprint $t) {
            $t->string('foto')->nullable()->after('valor');
        });
    }
    public function down(): void {
        Schema::table('variantes', function (Blueprint $t) { $t->dropColumn('foto'); });
    }
};
