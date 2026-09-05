<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pedido_items')) return;

        Schema::table('pedido_items', function (Blueprint $t) {
            if (! Schema::hasColumn('pedido_items', 'cojines_secundario')) {
                $t->text('cojines_secundario')->nullable();
            }
            foreach ([
                'foto_modelo', 'foto_tapiz_principal', 'foto_tapiz_secundario',
                'foto_cojines', 'foto_cojines_secundario', 'foto_lacado',
            ] as $c) {
                if (! Schema::hasColumn('pedido_items', $c)) {
                    $t->string($c)->nullable();
                }
            }
        });
    }

    public function down(): void {}
};
