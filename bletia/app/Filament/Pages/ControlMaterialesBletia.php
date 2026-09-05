<?php

namespace App\Filament\Pages;

use App\Support\Acl;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class ControlMaterialesBletia extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Control de materiales';
    protected static ?string $title = 'Control de materiales';
    protected static string|\UnitEnum|null $navigationGroup = 'Inventario';
    protected static ?int $navigationSort = 3;
    protected string $view = 'filament.pages.control-materiales-bletia';

    public static function canAccess(): bool
    {
        return Acl::esAdmin() || Acl::esOperaciones() || in_array(Acl::rol(), ['bodega', 'produccion'], true);
    }

    /** Detalle por solicitud: qué pidió el taller, qué se entregó, cuánto usó. */
    public function solicitudes(): array
    {
        $rows = DB::table('movimientos_material as m')
            ->leftJoin('materias_primas as mp', 'mp.id', '=', 'm.materia_prima_id')
            ->leftJoin('pedidos as p', 'p.id', '=', 'm.pedido_id')
            ->whereNotNull('m.pedido_id')
            ->selectRaw("
                m.pedido_id,
                COALESCE(p.folio, CONCAT('#', m.pedido_id)) as pedido,
                m.materia_prima_id,
                COALESCE(mp.nombre,'?') as materia,
                mp.unidad as unidad,
                SUM(CASE WHEN m.tipo='solicitud' THEN m.cantidad ELSE 0 END) as solicitado,
                SUM(CASE WHEN m.tipo='entrega'   THEN m.cantidad ELSE 0 END) as entregado,
                SUM(CASE WHEN m.tipo='uso'       THEN m.cantidad ELSE 0 END) as usado
            ")
            ->groupBy('m.pedido_id', 'pedido', 'm.materia_prima_id', 'materia', 'unidad')
            ->orderByDesc('m.pedido_id')
            ->get();

        return $rows->map(function ($r) {
            $r->pendiente = round(($r->solicitado - $r->entregado), 2);
            $r->sobrante  = round(($r->entregado - $r->usado), 2);
            $r->pct_uso   = $r->entregado > 0 ? round($r->usado / $r->entregado * 100) : 0;
            return $r;
        })->all();
    }

    /** Resumen por materia prima: stock, mínimo y acumulados. */
    public function resumen(): array
    {
        $acum = DB::table('movimientos_material')
            ->selectRaw("
                materia_prima_id,
                SUM(CASE WHEN tipo='solicitud' THEN cantidad ELSE 0 END) as solicitado,
                SUM(CASE WHEN tipo='entrega'   THEN cantidad ELSE 0 END) as entregado,
                SUM(CASE WHEN tipo='uso'       THEN cantidad ELSE 0 END) as gastado,
                SUM(CASE WHEN tipo='entrada'   THEN cantidad ELSE 0 END) as entradas
            ")
            ->groupBy('materia_prima_id')
            ->get()->keyBy('materia_prima_id');

        return DB::table('materias_primas')->orderBy('nombre')->get()->map(function ($mp) use ($acum) {
            $a = $acum[$mp->id] ?? null;
            $mp->solicitado = $a->solicitado ?? 0;
            $mp->entregado  = $a->entregado ?? 0;
            $mp->gastado    = $a->gastado ?? 0;
            $mp->entradas   = $a->entradas ?? 0;
            $mp->bajo       = $mp->stock <= ($mp->minimo ?? 0);
            return $mp;
        })->all();
    }
}
