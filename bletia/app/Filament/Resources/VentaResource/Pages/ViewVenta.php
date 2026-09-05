<?php

namespace App\Filament\Resources\VentaResource\Pages;

use App\Filament\Resources\VentaResource;
use App\Models\Venta;
use App\Support\Acl;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;

class ViewVenta extends Page
{
    protected static string $resource = VentaResource::class;
    protected string $view = 'filament.ventas.view';

    public $record;

    public function mount($record): void
    {
        $this->record = Venta::with(['cliente', 'pedido', 'vendedor', 'sriComprobante'])->findOrFail($record);
    }

    public function getTitle(): string
    {
        return 'Comprobante ' . ($this->record->numero_comprobante ?: $this->record->nro_factura ?: ('#' . $this->record->id));
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('pdf')->label('Descargar PDF')->icon('heroicon-o-arrow-down-tray')->color('gray')
                ->action(function () {
                    $path = VentaResource::rutaPdfPublica($this->record);
                    if (! $path || ! is_file($path)) {
                        // intentar generarlo
                        try {
                            if ($this->record->tipo_comprobante === 'nota_venta') {
                                $path = \App\Services\Sri\NotaVenta::generar($this->record);
                            } elseif ($this->record->sriComprobante) {
                                $path = \App\Services\Sri\Ride::generar($this->record->sriComprobante);
                            }
                        } catch (\Throwable $e) {}
                    }
                    if (! $path || ! is_file($path)) {
                        Notification::make()->warning()->title('PDF no disponible')->send();
                        return null;
                    }
                    return response()->download($path);
                }),
            Actions\Action::make('reenviar')->label('Reenviar correo')->icon('heroicon-o-envelope')->color('primary')
                ->requiresConfirmation()
                ->modalDescription(fn () => 'Reenviar al correo del cliente (' . ($this->record->cliente->email ?? 'sin correo') . ').')
                ->action(function () {
                    $r = VentaResource::reenviarPublico($this->record);
                    Notification::make()->{$r['ok'] ? 'success' : 'danger'}()
                        ->title($r['ok'] ? 'Correo reenviado' : 'No se pudo reenviar')->body($r['msg'])->send();
                }),
            Actions\Action::make('anular')->label('Anular documento')->icon('heroicon-o-x-circle')->color('danger')
                ->visible(fn () => (\App\Support\Acl::esAdmin() || \App\Support\Acl::rol() === 'contabilidad') && $this->record->estado !== 'anulada')
                ->requiresConfirmation()
                ->modalHeading('Anular comprobante')
                ->modalDescription(function () {
                    if ($this->record->tipo_comprobante !== 'factura') {
                        return 'Se marcará la nota de venta como anulada (conserva su número).';
                    }
                    $base = 'Se emitirá una Nota de Crédito al SRI que reversa esta factura. La factura original se conserva.';
                    $fecha = $this->record->fecha ?? $this->record->created_at;
                    if ($fecha && \App\Services\Sri\AnularFactura::fueraDePlazoAnulacion($fecha)) {
                        $base .= ' ⚠️ Fuera de plazo de anulación en línea SRI (día 7 del mes siguiente a la emisión) — solo procede Nota de Crédito, que sigue siendo válida sin límite de 12 meses.';
                    }
                    return $base;
                })
                ->form([
                    \Filament\Forms\Components\Textarea::make('motivo')->label('Motivo de anulación')->required()->rows(2)
                        ->placeholder('Ej: error en datos, devolución, anulación de venta...'),
                ])
                ->action(function (array $data) {
                    $motivo = $data['motivo'] ?? 'Anulación';
                    if ($this->record->tipo_comprobante === 'factura') {
                        $r = \App\Services\Sri\AnularFactura::porVenta($this->record->fresh(), $motivo);
                        \Filament\Notifications\Notification::make()
                            ->{($r['ok'] ?? false) ? 'success' : 'danger'}()
                            ->title(($r['ok'] ?? false) ? 'Factura anulada' : 'No se pudo anular')
                            ->body($r['msg'] ?? '')->persistent()->send();
                    } else {
                        $this->record->update(['estado' => 'anulada']);
                        if ($this->record->pedido_id) {
                            \Illuminate\Support\Facades\DB::table('pedidos')->where('id', $this->record->pedido_id)->update(['nro_factura' => null, 'facturado_at' => null]);
                        }
                        if (class_exists(\App\Models\Bitacora::class)) {
                            \App\Models\Bitacora::registrar('anuló nota de venta', 'Venta', $this->record->id, $motivo);
                        }
                        \Filament\Notifications\Notification::make()->success()->title('Nota de venta anulada')->send();
                    }
                    $this->record->refresh();
                }),
        ];
    }

    protected function getViewData(): array
    {
        $esVentaDirecta = empty($this->record->pedido_id);

        if ($esVentaDirecta) {
            // venta directa de stock: los items vienen del comprobante SRI (factura) o se reconstruyen
            // desde la BD del comprobante; no hay fila en pedido_items porque no pasó por fabricación.
            $compTmp = $this->record->sri_comprobante_id ? \App\Models\SriComprobante::find($this->record->sri_comprobante_id) : null;
            $detallesTmp = $compTmp && ! empty($compTmp->detalles)
                ? (is_array($compTmp->detalles) ? $compTmp->detalles : (json_decode($compTmp->detalles, true) ?: []))
                : [];
            $items = collect($detallesTmp)->map(fn ($d) => (object) [
                'nombre' => $d['descripcion'] ?? 'Producto',
                'variantes' => null,
                'cantidad' => $d['cantidad'] ?? 1,
                'subtotal' => round((float) ($d['cantidad'] ?? 1) * (float) ($d['precio_unitario'] ?? 0) * (1 + ((float) ($d['iva_rate'] ?? 15) / 100)), 2),
            ]);
            $pagos = \App\Services\Sri\FormasPago::desglosePorVenta($this->record);
        } else {
            $items = DB::table('pedido_items')->where('pedido_id', $this->record->pedido_id)->get();
            $pagos = [];
            if ($this->record->pedido) {
                $pagos = \App\Services\Sri\FormasPago::desglosePedido($this->record->pedido);
            }
        }

        // totales: calcular igual que el PDF (desde detalles del comprobante SRI o desde items del pedido)
        $subtotal = 0.0; $iva = 0.0; $total = 0.0;
        $comp = $this->record->sri_comprobante_id ? \App\Models\SriComprobante::find($this->record->sri_comprobante_id) : null;

        if ($comp && ! empty($comp->detalles)) {
            // factura: usar los detalles del comprobante (precio_unitario es base sin IVA)
            $detalles = is_array($comp->detalles) ? $comp->detalles : (json_decode($comp->detalles, true) ?: []);
            foreach ($detalles as $d) {
                $cant = (float) ($d['cantidad'] ?? 1);
                $pu = (float) ($d['precio_unitario'] ?? 0);
                $desc = (float) ($d['descuento'] ?? 0);
                $base = ($cant * $pu) - $desc;
                $rate = (float) ($d['iva_rate'] ?? 15);
                $subtotal += $base;
                $iva += round($base * $rate / 100, 2);
            }
            $total = round($subtotal + $iva, 2);
        } else {
            // nota de venta o sin comprobante: usar campos de la venta, o calcular desde items (PVP con IVA incl.)
            $total = (float) $this->record->total;
            if ($total <= 0 && ! $esVentaDirecta) {
                $totalItems = (float) DB::table('pedido_items')->where('pedido_id', $this->record->pedido_id)->sum('subtotal');
                $total = round($totalItems, 2);
            }
            $subtotal = round($total / 1.15, 2);
            $iva = round($total - $subtotal, 2);
            if ((float) $this->record->subtotal > 0) {
                $subtotal = (float) $this->record->subtotal;
                $iva = (float) $this->record->iva;
                $total = (float) $this->record->total;
            }
        }

        // pagado y saldo (recibos validados; del pedido o de la venta directa según el caso)
        if ($esVentaDirecta) {
            $pagado = (float) DB::table('recibos')->where('venta_id', $this->record->id)->where('validado', 1)->sum('monto');
        } else {
            $pagado = (float) DB::table('recibos')->where('pedido_id', $this->record->pedido_id)->where('validado', 1)->sum('monto');
        }
        $saldo = round($total - $pagado, 2);

        return ['v' => $this->record, 'items' => $items, 'pagos' => $pagos,
            'subtotalCalc' => $subtotal, 'ivaCalc' => $iva, 'totalCalc' => $total,
            'pagadoCalc' => round($pagado, 2), 'saldoCalc' => max(0, $saldo)];
    }
}
