<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('pedidos', function (Blueprint $t) {
        if (!Schema::hasColumn('pedidos','bodega_despacho_id')) $t->foreignId('bodega_despacho_id')->nullable()->after('estado')->constrained('locales')->nullOnDelete();
        if (!Schema::hasColumn('pedidos','despachado_at')) $t->timestamp('despachado_at')->nullable()->after('bodega_despacho_id');
    }); }
    public function down(): void { Schema::table('pedidos', function (Blueprint $t) { $t->dropColumn(['bodega_despacho_id','despachado_at']); }); }
};
