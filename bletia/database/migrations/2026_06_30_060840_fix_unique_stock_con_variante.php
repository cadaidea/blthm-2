<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // quitar la restricción vieja (producto_id + local_id), que ya no aplica
        // porque varias variantes del mismo producto pueden compartir local.
        try {
            Schema::table('stock', function (Blueprint $table) {
                $table->dropUnique('stock_producto_id_local_id_unique');
            });
        } catch (\Throwable $e) {
            // si el nombre real de la constraint es distinto, lo buscamos dinámicamente
            $idx = DB::select("SHOW INDEX FROM stock WHERE Key_name LIKE '%producto_id%local_id%' AND Non_unique = 0");
            foreach ($idx as $i) {
                try { DB::statement("ALTER TABLE stock DROP INDEX `{$i->Key_name}`"); } catch (\Throwable $e2) {}
            }
        }

        // nueva restricción única: producto_id + local_id + variante_id
        // (variante_id puede ser NULL para stock general, MySQL permite múltiples NULL en unique)
        Schema::table('stock', function (Blueprint $table) {
            $table->unique(['producto_id', 'local_id', 'variante_id'], 'stock_producto_local_variante_unique');
        });
    }

    public function down(): void
    {
        Schema::table('stock', function (Blueprint $table) {
            $table->dropUnique('stock_producto_local_variante_unique');
        });
        Schema::table('stock', function (Blueprint $table) {
            $table->unique(['producto_id', 'local_id'], 'stock_producto_id_local_id_unique');
        });
    }
};
