<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('pedidos', function (Blueprint $t) {
            if (! Schema::hasColumn('pedidos','nombre_recibe'))   $t->string('nombre_recibe')->nullable();
            if (! Schema::hasColumn('pedidos','horario_entrega')) $t->string('horario_entrega')->nullable();
        });
        Schema::table('pedido_items', function (Blueprint $t) {
            if (! Schema::hasColumn('pedido_items','lado')) $t->string('lado')->nullable();
        });
    }
    public function down(): void {
        Schema::table('pedidos', function (Blueprint $t) {
            foreach (['nombre_recibe','horario_entrega'] as $c) if (Schema::hasColumn('pedidos',$c)) $t->dropColumn($c);
        });
        Schema::table('pedido_items', function (Blueprint $t) {
            if (Schema::hasColumn('pedido_items','lado')) $t->dropColumn('lado');
        });
    }
};
