<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimientos_material', function (Blueprint $t) {
            if (! Schema::hasColumn('movimientos_material', 'recibido_nombre'))  $t->string('recibido_nombre')->nullable();
            if (! Schema::hasColumn('movimientos_material', 'recibido_cedula'))  $t->string('recibido_cedula')->nullable();
            if (! Schema::hasColumn('movimientos_material', 'firma'))            $t->longText('firma')->nullable();  // dataURL base64
            if (! Schema::hasColumn('movimientos_material', 'pdf_entrega'))      $t->string('pdf_entrega')->nullable();
            if (! Schema::hasColumn('movimientos_material', 'entregado_at'))     $t->timestamp('entregado_at')->nullable();
        });
    }
    public function down(): void {}
};
