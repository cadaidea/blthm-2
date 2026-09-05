<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campanias', function (Blueprint $t) {
            if (! Schema::hasColumn('campanias', 'contenido_json')) {
                $t->json('contenido_json')->nullable()->after('preheader');
            }
        });
    }
    public function down(): void
    {
        Schema::table('campanias', function (Blueprint $t) {
            if (Schema::hasColumn('campanias', 'contenido_json')) $t->dropColumn('contenido_json');
        });
    }
};
