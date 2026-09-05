<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bitacora')) {
            Schema::create('bitacora', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id')->nullable();
                $t->string('user_nombre')->nullable();
                $t->string('rol')->nullable();
                $t->string('evento');               // creó | actualizó | eliminó | login | logout | acción
                $t->string('modulo')->nullable();   // Pedido, Recibo, Cliente, etc.
                $t->unsignedBigInteger('registro_id')->nullable();
                $t->string('descripcion')->nullable();
                $t->string('ip')->nullable();
                $t->timestamp('created_at')->nullable();
                $t->index(['modulo', 'created_at']);
                $t->index('user_id');
            });
        }
    }
    public function down(): void {}
};
