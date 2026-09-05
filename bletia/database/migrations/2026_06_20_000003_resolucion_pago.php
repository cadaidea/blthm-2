<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recibos', function (Blueprint $t) {
            if (! Schema::hasColumn('recibos', 'resolucion'))    $t->string('resolucion')->nullable(); // nota_credito | saldo_favor | reembolso
            if (! Schema::hasColumn('recibos', 'resuelto_por'))  $t->unsignedBigInteger('resuelto_por')->nullable();
            if (! Schema::hasColumn('recibos', 'resuelto_at'))   $t->timestamp('resuelto_at')->nullable();
        });
        Schema::table('clientes', function (Blueprint $t) {
            if (! Schema::hasColumn('clientes', 'saldo_favor')) $t->decimal('saldo_favor', 12, 2)->default(0);
        });
    }
    public function down(): void {}
};
