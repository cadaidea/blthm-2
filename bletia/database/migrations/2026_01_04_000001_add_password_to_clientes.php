<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('clientes', function (Blueprint $t) {
            if (! Schema::hasColumn('clientes', 'password')) {
                $t->string('password')->nullable()->after('email');
            }
            if (! Schema::hasColumn('clientes', 'remember_token')) {
                $t->rememberToken();
            }
        });
    }
    public function down(): void {
        Schema::table('clientes', function (Blueprint $t) {
            $t->dropColumn(['password', 'remember_token']);
        });
    }
};
