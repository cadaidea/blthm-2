<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ajustes') || ! class_exists(\App\Models\Ajuste::class)) return;

        $defaults = [
            'erp_email_dueno' => 'bletiaform@gmail.com',
            'erp_email_guias' => 'depillacela@gmail.com',
        ];
        foreach ($defaults as $clave => $valor) {
            if (! \App\Models\Ajuste::where('clave', $clave)->exists()) {
                \App\Models\Ajuste::set($clave, $valor);
            }
        }
    }

    public function down(): void {}
};
