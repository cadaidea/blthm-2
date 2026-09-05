<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('recursos', function (Blueprint $t) {
            if (! Schema::hasColumn('recursos','cupon_id')) $t->unsignedBigInteger('cupon_id')->nullable();
        });
    }
    public function down(): void {
        Schema::table('recursos', function (Blueprint $t) {
            if (Schema::hasColumn('recursos','cupon_id')) $t->dropColumn('cupon_id');
        });
    }
};
