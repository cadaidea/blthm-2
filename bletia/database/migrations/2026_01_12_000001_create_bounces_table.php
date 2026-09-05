<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('bounces', function (Blueprint $t) {
            $t->id();
            $t->foreignId('suscriptor_id')->nullable()->constrained('suscriptores')->nullOnDelete();
            $t->string('email', 190)->index();
            $t->enum('tipo', ['soft', 'hard', 'complaint'])->default('hard');
            $t->string('reason', 500)->nullable();
            $t->string('source', 40)->default('brevo');
            $t->timestamp('created_at')->nullable();
        });
    }
    public function down(): void { Schema::dropIfExists('bounces'); }
};
