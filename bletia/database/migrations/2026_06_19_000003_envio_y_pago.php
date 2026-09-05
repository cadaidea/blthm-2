<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $t) {
            if (! Schema::hasColumn('pedidos', 'retira_local'))    $t->boolean('retira_local')->default(false);
            if (! Schema::hasColumn('pedidos', 'direccion_envio')) $t->string('direccion_envio')->nullable();
            if (! Schema::hasColumn('pedidos', 'ciudad_envio'))    $t->string('ciudad_envio')->nullable();
            if (! Schema::hasColumn('pedidos', 'contacto_envio'))  $t->string('contacto_envio')->nullable();
        });
        Schema::table('recibos', function (Blueprint $t) {
            if (! Schema::hasColumn('recibos', 'nro_comprobante')) $t->string('nro_comprobante')->nullable();
            if (! Schema::hasColumn('recibos', 'lote'))            $t->string('lote')->nullable();
            if (! Schema::hasColumn('recibos', 'tipo_tarjeta'))    $t->string('tipo_tarjeta')->nullable();
            if (! Schema::hasColumn('recibos', 'recibido_por'))    $t->string('recibido_por')->nullable();
        });
    }
    public function down(): void {}
};
