<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('pedidos', function (Blueprint $t) {
            if (! Schema::hasColumn('pedidos','woo_id')) $t->unsignedBigInteger('woo_id')->nullable()->index();
        });
    }
    public function down(): void {
        Schema::table('pedidos', function (Blueprint $t) {
            if (Schema::hasColumn('pedidos','woo_id')) $t->dropColumn('woo_id');
        });
    }
};
