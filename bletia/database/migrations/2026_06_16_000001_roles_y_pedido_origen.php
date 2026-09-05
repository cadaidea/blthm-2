<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'rol')) {
            Schema::table('users', fn (Blueprint $t) => $t->string('rol')->default('admin')->after('email'));
        }
        if (! Schema::hasColumn('users', 'local_id')) {
            Schema::table('users', fn (Blueprint $t) => $t->unsignedBigInteger('local_id')->nullable()->after('rol'));
        }
        if (! Schema::hasColumn('users', 'activo')) {
            Schema::table('users', fn (Blueprint $t) => $t->boolean('activo')->default(true)->after('local_id'));
        }
        // usuarios existentes => admin
        DB::table('users')->whereNull('rol')->orWhere('rol', '')->update(['rol' => 'admin']);

        foreach (['vendedor_id' => 'cliente_id', 'local_id' => 'vendedor_id'] as $col => $after) {
            if (! Schema::hasColumn('pedidos', $col)) {
                Schema::table('pedidos', function (Blueprint $t) use ($col, $after) {
                    $t->unsignedBigInteger($col)->nullable()->after(Schema::hasColumn('pedidos', $after) ? $after : 'id');
                });
            }
        }
        if (! Schema::hasColumn('pedidos', 'fecha_entrega')) {
            Schema::table('pedidos', fn (Blueprint $t) => $t->date('fecha_entrega')->nullable());
        }
    }

    public function down(): void
    {
        // no destructivo
    }
};
