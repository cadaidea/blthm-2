<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('productos', function (Blueprint $t) {
        if (!Schema::hasColumn('productos','mto_texto')) $t->string('mto_texto')->nullable()->after('permitir_pedido');
    }); }
    public function down(): void { Schema::table('productos', fn (Blueprint $t) => $t->dropColumn('mto_texto')); }
};
