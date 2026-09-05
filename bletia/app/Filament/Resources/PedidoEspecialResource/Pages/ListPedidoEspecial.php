<?php
namespace App\Filament\Resources\PedidoEspecialResource\Pages;



use Illuminate\Support\Facades\DB;
use App\Services\Traza;
use App\Support\Acl;
use App\Filament\Resources\PedidoEspecialResource;
use App\Models\Cliente;
use App\Models\PedidoEspecial;
use App\Models\PedidoItemErp;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Services\Folios;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ListPedidoEspecial extends ListRecords
{
    protected static string $resource = PedidoEspecialResource::class;

    public function getModel(): string
    {
        return PedidoEspecial::class;
    }

    protected function getTableQuery(): ?\Illuminate\Database\Eloquent\Builder
    {
        $q = \App\Models\PedidoEspecial::query();
        if (! \App\Support\Acl::esAdmin() && \App\Support\Acl::rol() === 'vendedor') {
            $q->where('vendedor_id', auth()->id());
        }
        $enProceso = ['enviado_proveedor', 'en_fabricacion', 'listo_proveedor', 'en_bodega', 'listo_despacho', 'despachado'];
        switch ($this->activeTab) {
            case 'pendientes': $q->where('estado_erp', 'pendiente'); break;
            case 'por_aprobar': $q->where('estado_erp', 'por_aprobar'); break;
            case 'aprobados': $q->where('estado_erp', 'aprobado'); break;
            case 'en_bodega': $q->where('estado_erp', 'en_bodega'); break;
            case 'fabricacion': $q->whereIn('estado_erp', $enProceso); break;
            case 'entregados': $q->where('estado_erp', 'entregado'); break;
            case 'anulados': $q->whereIn('estado_erp', ['anulado', 'cancelado']); break;
        }
        return $q;
    }


    protected static function campoConFoto(string $name, string $label, string $fotoName): array
    {
        return [
            Forms\Components\TextInput::make($name)->label($label),
            Forms\Components\FileUpload::make($fotoName)->label('Foto ' . strtolower($label))->image()->directory('pedido-local')->imageEditor(),
        ];
    }

        protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('exportar')->label('Exportar Excel')->icon('heroicon-o-arrow-down-tray')->color('gray')
                ->action(function () {
                    $cols = ['folio'=>'Folio','estado_erp'=>'Estado','forma_venta'=>'Forma','total'=>'Total','fecha_comprometida'=>'Entrega','created_at'=>'Creado'];
                    $regs = \App\Models\PedidoEspecial::query()->get();
                    $hoja = \App\Services\ExportadorExcel::deRegistros($regs, $cols);
                    $archivo = 'pedidos-' . now()->format('Ymd-His') . '.xlsx';
                    $path = \App\Services\ExportadorExcel::generar(['pedidos' => $hoja], $archivo);
                    \App\Models\Bitacora::registrar('exporto', 'Excel', null, $archivo);
                    return response()->download($path, $archivo)->deleteFileAfterSend(true);
                }),

            Actions\Action::make('nuevoLocal')->label('Nuevo pedido (venta local)')->icon('heroicon-o-plus')
                ->url(fn () => \App\Filament\Pages\CrearPedidoBletia::getUrl()),
        ];
    }


        public function getTabs(): array
    {
        $tabs = [
            'todos' => \Filament\Schemas\Components\Tabs\Tab::make('Todos'),
            'pendientes' => \Filament\Schemas\Components\Tabs\Tab::make('Pendientes'),
        ];
        if (\App\Support\Acl::puedeAprobar()) {
            $tabs['por_aprobar'] = \Filament\Schemas\Components\Tabs\Tab::make('Por aprobar');
        }
        $tabs['aprobados'] = \Filament\Schemas\Components\Tabs\Tab::make('Aprobados');
        $tabs['fabricacion'] = \Filament\Schemas\Components\Tabs\Tab::make('En fabricación');
        $tabs['en_bodega'] = \Filament\Schemas\Components\Tabs\Tab::make('En bodega');
        $tabs['entregados'] = \Filament\Schemas\Components\Tabs\Tab::make('Entregados');
        $tabs['anulados'] = \Filament\Schemas\Components\Tabs\Tab::make('Anulados');
        return $tabs;
    }

    /** ¿el producto del item tiene specs de cierto tipo? (según tabla variantes) */
    protected static function prodTiene($get, string $attr): bool
    {
        $pid = $get('producto_id');
        if (! $pid) return false;
        return \Illuminate\Support\Facades\DB::table('variantes')->where('producto_id', $pid)
            ->where('nombre', 'like', '%' . $attr . '%')->exists();
    }
}
