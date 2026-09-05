<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('transportistas')) return;
        Schema::create('transportistas', function (Blueprint $t) {
            $t->id();
            $t->string('nombre');
            $t->string('email')->nullable();
            $t->string('celular', 40)->nullable();
            $t->string('empresa', 120)->nullable();
            $t->boolean('activo')->default(true);
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('transportistas'); }
};
