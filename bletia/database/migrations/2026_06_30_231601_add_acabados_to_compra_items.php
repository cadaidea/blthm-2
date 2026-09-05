<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('compra_items', function (Blueprint $table) {
            $cols = ['tapiz_principal', 'foto_tapiz_principal', 'tapiz_secundario', 'foto_tapiz_secundario', 'cojines', 'foto_cojines', 'lacado', 'foto_lacado', 'notas_adicionales'];
            foreach ($cols as $c) {
                if (! Schema::hasColumn('compra_items', $c)) $table->string($c, 500)->nullable();
            }
        });
    }
    public function down(): void
    {
        Schema::table('compra_items', function (Blueprint $table) {
            $table->dropColumn(['tapiz_principal', 'foto_tapiz_principal', 'tapiz_secundario', 'foto_tapiz_secundario', 'cojines', 'foto_cojines', 'lacado', 'foto_lacado', 'notas_adicionales']);
        });
    }
};
