<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedido_items', function (Blueprint $t) {
            if (! Schema::hasColumn('pedido_items', 'pvp_base'))         $t->decimal('pvp_base', 10, 2)->nullable();
            if (! Schema::hasColumn('pedido_items', 'descuento_pct'))    $t->decimal('descuento_pct', 5, 2)->nullable();
            if (! Schema::hasColumn('pedido_items', 'valor_adicional'))  $t->decimal('valor_adicional', 10, 2)->nullable();
            if (! Schema::hasColumn('pedido_items', 'motivo_adicional')) $t->string('motivo_adicional')->nullable();
            if (! Schema::hasColumn('pedido_items', 'foto_adicional'))   $t->string('foto_adicional')->nullable();
        });
    }
    public function down(): void {}
};
