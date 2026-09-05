<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('documentos')) return;
        Schema::create('documentos', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('pedido_id')->nullable()->index();
            $t->unsignedBigInteger('despacho_id')->nullable()->index();
            $t->string('tipo', 40);
            $t->string('url', 255)->nullable();
            $t->string('ruta', 255)->nullable();
            $t->string('nombre_archivo', 160)->nullable();
            $t->timestamp('creado_en')->nullable();
        });
    }
    public function down(): void { Schema::dropIfExists('documentos'); }
};
