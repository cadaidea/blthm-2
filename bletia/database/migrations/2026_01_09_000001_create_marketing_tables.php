<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('listas', function (Blueprint $t) {
            $t->id();
            $t->string('nombre');
            $t->string('slug')->unique();
            $t->text('descripcion')->nullable();
            $t->boolean('publica')->default(true);
            $t->timestamps();
        });
        Schema::create('suscriptores', function (Blueprint $t) {
            $t->id();
            $t->string('email', 190)->unique();
            $t->string('nombre')->nullable();
            $t->string('apellido')->nullable();
            $t->enum('estado', ['pendiente', 'confirmado', 'baja', 'rebotado'])->default('pendiente');
            $t->string('token', 64)->index();
            $t->string('ip', 45)->nullable();
            $t->string('source', 60)->default('form');
            $t->timestamp('confirmed_at')->nullable();
            $t->timestamp('unsubscribed_at')->nullable();
            $t->timestamps();
            $t->index('estado');
        });
        Schema::create('lista_suscriptor', function (Blueprint $t) {
            $t->foreignId('lista_id')->constrained('listas')->cascadeOnDelete();
            $t->foreignId('suscriptor_id')->constrained('suscriptores')->cascadeOnDelete();
            $t->primary(['lista_id', 'suscriptor_id']);
        });
        Schema::create('formularios', function (Blueprint $t) {
            $t->id();
            $t->string('nombre');
            $t->enum('tipo', ['inline', 'popup', 'slide_in', 'bar_top', 'bar_bottom', 'after_content'])->default('inline');
            $t->enum('estado', ['activo', 'pausado'])->default('activo');
            $t->json('lista_ids')->nullable();
            $t->string('titulo')->nullable();
            $t->text('descripcion')->nullable();
            $t->string('boton_texto')->nullable();
            $t->boolean('pedir_nombre')->default(false);
            $t->json('opciones')->nullable();
            $t->unsignedInteger('impresiones')->default(0);
            $t->unsignedInteger('conversiones')->default(0);
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('lista_suscriptor');
        Schema::dropIfExists('formularios');
        Schema::dropIfExists('suscriptores');
        Schema::dropIfExists('listas');
    }
};
