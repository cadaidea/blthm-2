<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empleados', function (Blueprint $t) {
            if (! Schema::hasColumn('empleados', 'tipo_contrato')) {
                $t->string('tipo_contrato', 30)->nullable()->after('relacion');
            }
        });
    }
    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $t) {
            if (Schema::hasColumn('empleados', 'tipo_contrato')) $t->dropColumn('tipo_contrato');
        });
    }
};
