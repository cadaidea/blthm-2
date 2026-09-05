<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recibos', function (Blueprint $t) {
            if (! Schema::hasColumn('recibos', 'validado'))      $t->boolean('validado')->default(false);
            if (! Schema::hasColumn('recibos', 'validado_por'))  $t->unsignedBigInteger('validado_por')->nullable();
            if (! Schema::hasColumn('recibos', 'validado_at'))   $t->timestamp('validado_at')->nullable();
            if (! Schema::hasColumn('recibos', 'comprobantes'))  $t->json('comprobantes')->nullable();
        });
        // recibos previos: darlos por validados para no romper saldos existentes
        DB::table('recibos')->where('validado', false)->update(['validado' => true, 'validado_at' => now()]);

        // ajuste correo contabilidad
        if (Schema::hasTable('ajustes') && ! DB::table('ajustes')->where('clave', 'erp_email_contabilidad')->exists()) {
            DB::table('ajustes')->insert(['clave' => 'erp_email_contabilidad', 'valor' => '', 'created_at' => now(), 'updated_at' => now()]);
        }
    }
    public function down(): void {}
};
