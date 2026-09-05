<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
    public function up(): void {
        DB::statement("ALTER TABLE formularios MODIFY tipo VARCHAR(20) NOT NULL DEFAULT 'inline'");
    }
    public function down(): void {
        DB::statement("ALTER TABLE formularios MODIFY tipo VARCHAR(20) NOT NULL DEFAULT 'inline'");
    }
};
