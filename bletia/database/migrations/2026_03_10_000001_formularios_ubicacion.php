<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (! Schema::hasTable('formularios')) return;
        Schema::table('formularios', function (Blueprint $t) {
            if (! Schema::hasColumn('formularios', 'ubicacion'))     $t->string('ubicacion', 30)->nullable();
            if (! Schema::hasColumn('formularios', 'ambito'))        $t->string('ambito', 20)->default('todo');
            if (! Schema::hasColumn('formularios', 'entre_parrafo')) $t->unsignedInteger('entre_parrafo')->default(2);
            if (! Schema::hasColumn('formularios', 'premarcado'))    $t->boolean('premarcado')->default(false);
        });
    }
    public function down(): void {}
};
