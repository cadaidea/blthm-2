<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('recibos', function (Blueprint $t) {
            if (! Schema::hasColumn('recibos','pagador_nombre'))   $t->string('pagador_nombre')->nullable();
            if (! Schema::hasColumn('recibos','pagador_id_num'))   $t->string('pagador_id_num')->nullable();
            if (! Schema::hasColumn('recibos','pagador_contacto')) $t->string('pagador_contacto')->nullable();
            if (! Schema::hasColumn('recibos','pagador_email'))    $t->string('pagador_email')->nullable();
        });
        Schema::table('pedidos', function (Blueprint $t) {
            if (! Schema::hasColumn('pedidos','anticipo_solicitado_at')) $t->timestamp('anticipo_solicitado_at')->nullable();
        });
    }
    public function down(): void {
        Schema::table('recibos', function (Blueprint $t) {
            foreach (['pagador_nombre','pagador_id_num','pagador_contacto','pagador_email'] as $c)
                if (Schema::hasColumn('recibos',$c)) $t->dropColumn($c);
        });
        Schema::table('pedidos', function (Blueprint $t) {
            if (Schema::hasColumn('pedidos','anticipo_solicitado_at')) $t->dropColumn('anticipo_solicitado_at');
        });
    }
};
