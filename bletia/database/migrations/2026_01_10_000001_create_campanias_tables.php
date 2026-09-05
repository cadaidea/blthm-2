<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('campanias', function (Blueprint $t) {
            $t->id();
            $t->string('asunto');
            $t->string('preheader')->nullable();
            $t->longText('cuerpo_html');
            $t->json('lista_ids')->nullable();
            $t->enum('estado', ['borrador', 'programada', 'enviando', 'enviada', 'pausada', 'fallida'])->default('borrador');
            $t->timestamp('programada_at')->nullable();
            $t->timestamp('enviada_at')->nullable();
            $t->unsignedInteger('total_destinatarios')->default(0);
            $t->unsignedInteger('total_enviados')->default(0);
            $t->unsignedInteger('total_aperturas')->default(0);
            $t->unsignedInteger('total_clics')->default(0);
            $t->timestamps();
            $t->index('estado');
            $t->index('programada_at');
        });
        Schema::create('campania_emails', function (Blueprint $t) {
            $t->id();
            $t->foreignId('campania_id')->constrained('campanias')->cascadeOnDelete();
            $t->foreignId('suscriptor_id')->constrained('suscriptores')->cascadeOnDelete();
            $t->enum('estado', ['cola', 'enviado', 'fallido', 'abierto', 'clicado'])->default('cola');
            $t->string('tracking_token', 64)->unique();
            $t->unsignedTinyInteger('intentos')->default(0);
            $t->string('error', 255)->nullable();
            $t->timestamp('enviado_at')->nullable();
            $t->timestamp('abierto_at')->nullable();
            $t->unsignedInteger('clics')->default(0);
            $t->unique(['campania_id', 'suscriptor_id']);
            $t->index(['campania_id', 'estado']);
        });
        Schema::create('campania_clics', function (Blueprint $t) {
            $t->id();
            $t->foreignId('campania_email_id')->constrained('campania_emails')->cascadeOnDelete();
            $t->string('url', 2048);
            $t->timestamp('created_at')->nullable();
        });
    }
    public function down(): void {
        Schema::dropIfExists('campania_clics');
        Schema::dropIfExists('campania_emails');
        Schema::dropIfExists('campanias');
    }
};
