<?php

use App\Services\Folios;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('secuencias')) {
            Schema::create('secuencias', function (Blueprint $t) {
                $t->id();
                $t->string('tipo', 20)->unique();
                $t->unsignedBigInteger('ultimo')->default(0);
                $t->timestamps();
            });
        }

        if (Schema::hasTable('pedidos')) {
            Schema::table('pedidos', function (Blueprint $t) {
                if (! Schema::hasColumn('pedidos', 'folio')) $t->string('folio', 30)->nullable()->index();
                if (! Schema::hasColumn('pedidos', 'folio_of')) $t->string('folio_of', 120)->nullable();
                if (! Schema::hasColumn('pedidos', 'folio_anulacion')) $t->string('folio_anulacion', 30)->nullable();
            });
        }
        if (Schema::hasTable('recibos') && ! Schema::hasColumn('recibos', 'folio')) {
            Schema::table('recibos', fn (Blueprint $t) => $t->string('folio', 30)->nullable()->index());
        }
        if (Schema::hasTable('despachos') && ! Schema::hasColumn('despachos', 'folio')) {
            Schema::table('despachos', fn (Blueprint $t) => $t->string('folio', 30)->nullable()->index());
        }

        // Backfill: numerar lo existente en orden de creación y dejar las series listas.
        try {
            if (Schema::hasColumn('pedidos', 'folio')) {
                foreach (DB::table('pedidos')->whereNull('folio')->orderBy('id')->get(['id', 'tipo_erp']) as $p) {
                    $tipo = ($p->tipo_erp === 'local') ? 'VL' : 'PED';
                    DB::table('pedidos')->where('id', $p->id)->update(['folio' => Folios::next($tipo)]);
                }
            }
            if (Schema::hasTable('recibos') && Schema::hasColumn('recibos', 'folio')) {
                foreach (DB::table('recibos')->whereNull('folio')->orderBy('id')->get(['id']) as $r) {
                    DB::table('recibos')->where('id', $r->id)->update(['folio' => Folios::next('REC')]);
                }
            }
            if (Schema::hasTable('despachos') && Schema::hasColumn('despachos', 'folio')) {
                foreach (DB::table('despachos')->whereNull('folio')->orderBy('id')->get(['id']) as $d) {
                    DB::table('despachos')->where('id', $d->id)->update(['folio' => Folios::next('GUI')]);
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function down(): void {}
};
