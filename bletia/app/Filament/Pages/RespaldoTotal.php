<?php
namespace App\Filament\Pages;

use App\Services\ExportadorExcel;
use App\Support\Acl;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\DB;

class RespaldoTotal extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-circle-stack';
    protected static ?string $navigationLabel = 'Respaldo total';
    protected static ?string $title = 'Respaldo total (Excel)';
    protected static string|\UnitEnum|null $navigationGroup = 'Sistema';
    protected static ?int $navigationSort = 1;
    protected string $view = 'filament.pages.respaldo-total';

    public static function shouldRegisterNavigation(): bool { return Acl::esAdmin() || Acl::esOperaciones(); }
    public static function canAccess(): bool { return Acl::esAdmin() || Acl::esOperaciones(); }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('descargar')
                ->label('Generar y descargar respaldo')
                ->icon('heroicon-o-arrow-down-tray')->color('success')
                ->action(fn () => $this->generar()),
        ];
    }

    public function generar()
    {
        // tablas a respaldar: tabla => [columnas reales]
        $tablas = [
            'Pedidos'        => ['pedidos', ['id','folio','estado_erp','tipo_erp','forma_venta','destino_fab','cliente_id','total','fecha_solicitada','fecha_comprometida','created_at']],
            'Items'          => ['pedido_items', ['id','pedido_id','nombre','variantes','cantidad','precio','subtotal','proveedor_id']],
            'Recibos'        => ['recibos', ['id','pedido_id','cliente_id','tipo','monto','metodo','validado','resolucion','created_at']],
            'Clientes'       => ['clientes', ['id','nombre','cedula_ruc','email','celular','telefono','ciudad','saldo_favor']],
            'Productos'      => ['productos', ['id','nombre','sku','precio','iva_rate','activo']],
            'Proveedores'    => ['proveedores', ['id','nombre','email','telefono','activo']],
            'Despachos'      => ['despachos', ['id','pedido_id','estado','ruta','created_at']],
            'Materias primas'=> ['materias_primas', ['id','nombre','unidad','stock','minimo','activo']],
            'Mov. material'  => ['movimientos_material', ['id','materia_prima_id','pedido_id','tipo','cantidad','estado','created_at']],
            'Usuarios'       => ['users', ['id','name','email','rol','local_id','activo']],
            'Auditoría'      => ['bitacora', ['id','user_nombre','rol','evento','modulo','registro_id','descripcion','created_at']],
        ];

        $hojas = [];
        foreach ($tablas as $titulo => [$tabla, $cols]) {
            if (! \Illuminate\Support\Facades\Schema::hasTable($tabla)) continue;
            $existentes = array_values(array_filter($cols, fn ($c) => \Illuminate\Support\Facades\Schema::hasColumn($tabla, $c)));
            $rows = DB::table($tabla)->orderBy('id')->get()->map(fn ($r) => array_map(fn ($c) => $r->$c ?? '', $existentes))->toArray();
            $hojas[$titulo] = ['headers' => $existentes, 'rows' => $rows];
        }

        $archivo = 'respaldo-bletia-' . now()->format('Ymd-His') . '.xlsx';
        $path = ExportadorExcel::generar($hojas, $archivo);
        \App\Models\Bitacora::registrar('exportó', 'Respaldo total', null, $archivo);
        return response()->download($path, $archivo)->deleteFileAfterSend(true);
    }
}
