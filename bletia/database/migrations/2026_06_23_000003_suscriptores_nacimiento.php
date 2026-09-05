<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('suscriptores', function (Blueprint $t) {
            if (! Schema::hasColumn('suscriptores','nacimiento')) $t->date('nacimiento')->nullable();
        });
    }
    public function down(): void {
        Schema::table('suscriptores', function (Blueprint $t) {
            if (Schema::hasColumn('suscriptores','nacimiento')) $t->dropColumn('nacimiento');
        });
    }
};
