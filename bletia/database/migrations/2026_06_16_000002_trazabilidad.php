<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // historial inmutable: 1 fila por evento
        if (! Schema::hasTable('pedido_historial')) {
            Schema::create('pedido_historial', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('pedido_id')->index();
                $t->string('accion');                 // vendido, enviado_aprobacion, aprobado, enviado_fabricacion, despachado, recibido
                $t->unsignedBigInteger('user_id')->nullable();
                $t->string('user_nombre')->nullable(); // snapshot por si el user cambia/borra
                $t->string('rol')->nullable();
                $t->text('nota')->nullable();
                $t->timestamp('created_at')->nullable();
            });
        }
        // campos fijos "quien actual" en pedidos
        $cols = [
            'vendido_por','vendido_at','aprobado_por','aprobado_at',
            'enviado_fab_por','enviado_fab_at','despachado_por','despachado_por_at',
        ];
        Schema::table('pedidos', function (Blueprint $t) {
            foreach ([
                'vendido_por'=>'int','vendido_at'=>'ts',
                'aprobado_por'=>'int','aprobado_at'=>'ts',
                'enviado_fab_por'=>'int','enviado_fab_at'=>'ts',
                'despachado_por'=>'int','despachado_por_at'=>'ts',
            ] as $c=>$tipo) {
                if (! Schema::hasColumn('pedidos',$c)) {
                    $tipo==='int' ? $t->unsignedBigInteger($c)->nullable() : $t->timestamp($c)->nullable();
                }
            }
        });
    }
    public function down(): void {}
};
