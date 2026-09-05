<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        $add = function (string $tabla, array $cols) {
            if (! Schema::hasTable($tabla)) return;
            Schema::table($tabla, function (Blueprint $t) use ($tabla, $cols) {
                foreach ($cols as $name => $fn) {
                    if (! Schema::hasColumn($tabla, $name)) $fn($t);
                }
            });
        };
        $add('locales', [
            'activo' => fn ($t) => $t->boolean('activo')->default(true),
        ]);
        $add('clientes', [
            'tipo_identificacion' => fn ($t) => $t->string('tipo_identificacion', 12)->default('cedula'),
        ]);
        $add('transportistas', [
            'tipo_identificacion' => fn ($t) => $t->string('tipo_identificacion', 12)->default('ruc'),
            'identificacion'      => fn ($t) => $t->string('identificacion', 20)->nullable(),
            'direccion'           => fn ($t) => $t->string('direccion', 255)->nullable(),
            'celular2'            => fn ($t) => $t->string('celular2', 40)->nullable(),
        ]);
        $add('despachos', [
            'listo'            => fn ($t) => $t->boolean('listo')->default(false),
            'conductor_nombre' => fn ($t) => $t->string('conductor_nombre')->nullable(),
            'conductor_nui'    => fn ($t) => $t->string('conductor_nui', 20)->nullable(),
            'conductor_celular' => fn ($t) => $t->string('conductor_celular', 40)->nullable(),
            'conductor_correo' => fn ($t) => $t->string('conductor_correo')->nullable(),
            'placa'            => fn ($t) => $t->string('placa', 15)->nullable(),
        ]);
        $add('productos', [
            'origen' => fn ($t) => $t->string('origen', 12)->default('propio'),
        ]);
    }
    public function down(): void {}
};
