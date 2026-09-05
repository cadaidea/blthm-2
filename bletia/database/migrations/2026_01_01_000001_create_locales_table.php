<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('locales', function (Blueprint $t) {
            $t->id();
            $t->string('nombre');
            $t->string('tipo')->default('local'); // local | bodega
            $t->boolean('activo')->default(true);
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('locales'); }
};
