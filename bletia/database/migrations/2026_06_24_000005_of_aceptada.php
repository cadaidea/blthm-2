<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('pedidos', function (Blueprint $t) {
            if (! Schema::hasColumn('pedidos','of_aceptada_at'))  $t->timestamp('of_aceptada_at')->nullable();
            if (! Schema::hasColumn('pedidos','of_aceptada_por')) $t->unsignedBigInteger('of_aceptada_por')->nullable();
        });
    }
    public function down(): void {
        Schema::table('pedidos', function (Blueprint $t) {
            foreach (['of_aceptada_at','of_aceptada_por'] as $c) if (Schema::hasColumn('pedidos',$c)) $t->dropColumn($c);
        });
    }
};
