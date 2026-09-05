<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('suscriptores', function (Blueprint $t) {
            if (! Schema::hasColumn('suscriptores','telefono')) $t->string('telefono',40)->nullable();
            if (! Schema::hasColumn('suscriptores','ciudad'))   $t->string('ciudad',120)->nullable();
        });
    }
    public function down(): void {
        Schema::table('suscriptores', function (Blueprint $t) {
            foreach (['telefono','ciudad'] as $c) if (Schema::hasColumn('suscriptores',$c)) $t->dropColumn($c);
        });
    }
};
