<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('editores', function (Blueprint $t) {
            $t->id();
            $t->string('nombre'); $t->string('slug')->unique();
            $t->string('cargo')->nullable();
            $t->text('bio')->nullable();
            $t->string('foto')->nullable();
            $t->string('web')->nullable(); $t->string('instagram')->nullable();
            $t->string('facebook')->nullable(); $t->string('x')->nullable(); $t->string('linkedin')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('editores'); }
};
