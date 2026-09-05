<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $t) {
            if (! Schema::hasColumn('pedidos', 'fecha_solicitada'))   $t->date('fecha_solicitada')->nullable();
            if (! Schema::hasColumn('pedidos', 'fecha_comprometida')) $t->date('fecha_comprometida')->nullable();
            if (! Schema::hasColumn('pedidos', 'forma_venta'))        $t->string('forma_venta')->nullable(); // online, stock, local
        });
        // migrar fecha_entrega -> fecha_comprometida si existia con datos
        if (Schema::hasColumn('pedidos', 'fecha_entrega') && Schema::hasColumn('pedidos', 'fecha_comprometida')) {
            DB::statement("UPDATE pedidos SET fecha_comprometida = fecha_entrega WHERE fecha_comprometida IS NULL AND fecha_entrega IS NOT NULL");
        }
    }
    public function down(): void {}
};
