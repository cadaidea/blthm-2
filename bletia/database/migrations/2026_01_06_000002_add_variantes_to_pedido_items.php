<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('pedido_items', function (Blueprint $t) {
        if (!Schema::hasColumn('pedido_items','variantes')) $t->string('variantes')->nullable()->after('nombre');
    }); }
    public function down(): void { Schema::table('pedido_items', fn (Blueprint $t) => $t->dropColumn('variantes')); }
};
