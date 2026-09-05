<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) columnas de perfil de autor directo en empleados
        Schema::table('empleados', function (Blueprint $t) {
            if (! Schema::hasColumn('empleados', 'slug')) $t->string('slug')->nullable()->unique()->after('nombre');
            if (! Schema::hasColumn('empleados', 'bio')) $t->text('bio')->nullable();
            if (! Schema::hasColumn('empleados', 'foto')) $t->string('foto')->nullable();
            if (! Schema::hasColumn('empleados', 'web')) $t->string('web')->nullable();
            if (! Schema::hasColumn('empleados', 'instagram')) $t->string('instagram')->nullable();
            if (! Schema::hasColumn('empleados', 'facebook')) $t->string('facebook')->nullable();
            if (! Schema::hasColumn('empleados', 'x')) $t->string('x')->nullable();
            if (! Schema::hasColumn('empleados', 'linkedin')) $t->string('linkedin')->nullable();
        });

        if (! Schema::hasTable('editores')) return;

        // 2) migrar cada editor existente a su empleado (por vínculo previo empleados.editor_id, o por nombre, o crear uno nuevo)
        $mapa = []; // editor_id antiguo => empleado_id nuevo
        $editores = DB::table('editores')->get();

        foreach ($editores as $ed) {
            $empleado = DB::table('empleados')->where('editor_id', $ed->id)->first();

            if (! $empleado) {
                $empleado = DB::table('empleados')->whereRaw('LOWER(nombre) = ?', [mb_strtolower($ed->nombre)])->first();
            }

            if (! $empleado) {
                $nuevoId = DB::table('empleados')->insertGetId([
                    'nombre' => $ed->nombre,
                    'relacion' => 'colaborador',
                    'activo' => true,
                    'sueldo' => 0,
                    'cargas_familiares' => 0,
                    'dias_vacaciones_anuales' => 15,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $empleadoId = $nuevoId;
            } else {
                $empleadoId = $empleado->id;
            }

            // evitar choque de slug único si dos filas ya tuvieran el mismo slug
            $slug = $ed->slug;
            $i = 1;
            while (DB::table('empleados')->where('slug', $slug)->where('id', '!=', $empleadoId)->exists()) {
                $slug = $ed->slug . '-' . (++$i);
            }

            DB::table('empleados')->where('id', $empleadoId)->update([
                'slug' => $slug,
                'bio' => $ed->bio,
                'foto' => $ed->foto,
                'web' => $ed->web,
                'instagram' => $ed->instagram,
                'facebook' => $ed->facebook,
                'x' => $ed->x,
                'linkedin' => $ed->linkedin,
            ]);

            $mapa[$ed->id] = $empleadoId;
        }

        // 3) repuntar articulos.editor_id de editores.id a empleados.id
        // IMPORTANTE: se actualiza cada fila por su propio "id" (no por editor_id),
        // para evitar que un UPDATE pise el resultado de otro cuando los rangos de
        // IDs viejos y nuevos se solapan.
        if (Schema::hasColumn('articulos', 'editor_id')) {
            try {
                DB::statement('ALTER TABLE articulos DROP FOREIGN KEY articulos_editor_id_foreign');
            } catch (\Throwable $e) {}

            $articulosAfectados = DB::table('articulos')->whereIn('editor_id', array_keys($mapa))->get(['id', 'editor_id']);
            foreach ($articulosAfectados as $art) {
                $nuevo = $mapa[$art->editor_id] ?? null;
                if ($nuevo !== null) {
                    DB::table('articulos')->where('id', $art->id)->update(['editor_id' => $nuevo]);
                }
            }

            try {
                Schema::table('articulos', function (Blueprint $t) {
                    $t->foreign('editor_id')->references('id')->on('empleados')->nullOnDelete();
                });
            } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        // No se revierte: la migración de datos hacia empleados se conserva por seguridad.
    }
};
