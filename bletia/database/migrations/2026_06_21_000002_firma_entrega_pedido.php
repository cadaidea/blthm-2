<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('despachos', function (Blueprint $t) {
            if (! Schema::hasColumn('despachos', 'recibido_nombre'))  $t->string('recibido_nombre')->nullable();
            if (! Schema::hasColumn('despachos', 'recibido_cedula'))  $t->string('recibido_cedula')->nullable();
            if (! Schema::hasColumn('despachos', 'firma_cliente'))    $t->longText('firma_cliente')->nullable();
            if (! Schema::hasColumn('despachos', 'pdf_entrega'))      $t->string('pdf_entrega')->nullable();
            if (! Schema::hasColumn('despachos', 'entregado_at'))     $t->timestamp('entregado_at')->nullable();
        });
    }
    public function down(): void {}
};
