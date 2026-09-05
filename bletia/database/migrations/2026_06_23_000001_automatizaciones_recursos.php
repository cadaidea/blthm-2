<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('automatizaciones')) {
            Schema::create('automatizaciones', function (Blueprint $t) {
                $t->id();
                $t->string('nombre');
                $t->string('tipo'); // post_publish|digest_daily|digest_weekly|birthday|abandoned_cart|back_in_stock|post_purchase|winback
                $t->string('estado')->default('activa'); // activa|pausada
                $t->json('lista_ids')->nullable();
                $t->string('asunto')->nullable();
                $t->string('preheader')->nullable();
                $t->longText('cuerpo_html')->nullable(); // soporta variables {first_name}, {product_name}, etc.
                $t->json('opciones')->nullable(); // { dias_winback, secuencia:[{dia,asunto,html}], category_ids, ... }
                $t->dateTime('last_run_at')->nullable();
                $t->timestamps();
                $t->index(['tipo', 'estado']);
            });
        }

        if (! Schema::hasTable('automatizacion_runs')) {
            Schema::create('automatizacion_runs', function (Blueprint $t) {
                $t->id();
                $t->foreignId('automatizacion_id')->index();
                $t->unsignedBigInteger('objeto_id');       // post id, order id, producto id, suscriptor id...
                $t->string('objeto_tipo', 30)->default('post');
                $t->unsignedBigInteger('campania_id')->nullable();
                $t->dateTime('created_at')->nullable();
                $t->unique(['automatizacion_id', 'objeto_id', 'objeto_tipo'], 'uniq_run');
            });
        }

        if (! Schema::hasTable('recursos')) {
            Schema::create('recursos', function (Blueprint $t) {
                $t->id();
                $t->string('nombre');
                $t->string('slug')->unique();
                $t->text('descripcion')->nullable();
                $t->string('tipo')->default('archivo'); // archivo|cupon
                $t->string('archivo')->nullable();      // path en disk public
                $t->string('cupon_codigo')->nullable();
                $t->json('lista_ids')->nullable();      // a qué listas se suscribe quien lo pide
                $t->boolean('activo')->default(true);
                $t->unsignedInteger('descargas')->default(0);
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('recurso_tokens')) {
            Schema::create('recurso_tokens', function (Blueprint $t) {
                $t->id();
                $t->foreignId('recurso_id')->index();
                $t->foreignId('suscriptor_id')->nullable()->index();
                $t->string('token', 64)->unique();
                $t->string('email')->nullable();
                $t->dateTime('expira_at')->nullable();
                $t->dateTime('usado_at')->nullable();
                $t->timestamps();
            });
        }

        // back_in_stock: avisos pedidos por clientes
        if (! Schema::hasTable('avisos_stock')) {
            Schema::create('avisos_stock', function (Blueprint $t) {
                $t->id();
                $t->foreignId('producto_id')->index();
                $t->string('email')->index();
                $t->boolean('notificado')->default(false);
                $t->dateTime('notificado_at')->nullable();
                $t->timestamps();
                $t->unique(['producto_id', 'email']);
            });
        }
    }

    public function down(): void
    {
        foreach (['automatizacion_runs', 'automatizaciones', 'recurso_tokens', 'recursos', 'avisos_stock'] as $tb) {
            Schema::dropIfExists($tb);
        }
    }
};
