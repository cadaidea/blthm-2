<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('locales', function (Blueprint $table) {
            if (! Schema::hasColumn('locales', 'direccion')) $table->string('direccion')->nullable()->after('nombre');
            if (! Schema::hasColumn('locales', 'ciudad')) $table->string('ciudad')->nullable()->after('direccion');
        });
    }
    public function down(): void
    {
        Schema::table('locales', function (Blueprint $table) {
            $table->dropColumn(['direccion', 'ciudad']);
        });
    }
};
