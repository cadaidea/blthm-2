<?php

namespace App\Filament\Pages;
use Filament\Schemas\Schema;

use App\Models\Cliente;
use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Models\Local;
use App\Models\Variante;
use App\Support\Acl;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

/**
 * Venta de stock DIRECTA: NO crea ningún registro en la tabla `pedidos`.
 * Va directo a Factura o Nota de venta usando el servicio VentaDirecta,
 * reutilizando el mismo motor SRI y la misma secuencia que los pedidos.
 * Incluye: variantes del producto, local/bodega de origen (con stock disponible real),
 * ajuste de precio (descuento/adicional con motivo), y pagos completos (efectivo,
 * transferencia, depósito, cheque, tarjeta) con los mismos campos que un recibo real.
 */
class VenderStockDirecto extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';
    protected static ?string $title = 'Vender stock';
    protected static bool $shouldRegisterNavigation = false;
    protected string $view = 'filament.pages.vender-stock-directo';
    protected static ?string $slug = 'vender-stock';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return in_array(Acl::rol(), ['admin', 'operaciones', 'contabilidad', 'vendedor'], true);
    }

    public function mount(): void
    {
        $this->form->fill([
            'tipo_comprobante' => 'factura',
            'items' => [['cantidad' => 1, 'ajuste_modo' => '', 'ajuste_signo' => 'menos']],
            'pagos' => [['metodo' => 'efectivo']],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Cliente')->schema([
                Forms\Components\Select::make('cliente_id')->label('Cliente')->required()
                    ->options(fn () => Cliente::orderBy('nombre')->pluck('nombre', 'id'))->searchable()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('nombre')->required(),
                        Forms\Components\TextInput::make('identificacion')->label('Cédula / RUC'),
                        Forms\Components\TextInput::make('email')->email(),
                        Forms\Components\TextInput::make('celular')->tel(),
                    ])
                    ->createOptionUsing(fn (array $data) => Cliente::create($data)->id),
            ]),

            \Filament\Schemas\Components\Section::make('Productos')->schema([
                Forms\Components\Repeater::make('items')->label('Ítems')
                    ->minItems(1)->defaultItems(1)->addActionLabel('Agregar producto')->columns(2)
                    ->schema([
                        Forms\Components\Select::make('producto_id')->label('Producto')->required()->columnSpanFull()
                            ->options(fn () => self::productosConStock())
                            ->searchable()->live()
                            ->helperText('Solo se muestran productos con stock disponible en algún local.')
                            ->afterStateUpdated(function ($state, \Filament\Schemas\Components\Utilities\Set $set) {
                                $set('variante_id', null);
                                $set('local_id', null);
                                if ($state && ($p = Producto::find($state))) {
                                    $set('precio', (float) $p->precio);
                                    $set('iva_rate', (float) $p->iva_rate);
                                }
                            }),

                        // variantes (igual que en pedidos: tapiz, lado, madera)
                        Forms\Components\Select::make('atr_1')->label('Tapiz')
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => self::prodTieneAttr($get('producto_id'), 1))
                            ->options(fn (\Filament\Schemas\Components\Utilities\Get $get) => self::opcionesProducto($get('producto_id'), 1))
                            ->live()->afterStateUpdated(fn (\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set) => self::resolverVariante($get, $set)),
                        Forms\Components\Select::make('atr_2')->label('Lado')
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => self::prodTieneAttr($get('producto_id'), 2))
                            ->options(fn (\Filament\Schemas\Components\Utilities\Get $get) => self::opcionesProducto($get('producto_id'), 2))
                            ->live()->afterStateUpdated(fn (\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set) => self::resolverVariante($get, $set)),
                        Forms\Components\Select::make('atr_3')->label('Madera')
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => self::prodTieneAttr($get('producto_id'), 3))
                            ->options(fn (\Filament\Schemas\Components\Utilities\Get $get) => self::opcionesProducto($get('producto_id'), 3))
                            ->live()->afterStateUpdated(fn (\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set) => self::resolverVariante($get, $set)),
                        Forms\Components\Hidden::make('variante_id'),

                        // local/bodega de origen, mostrando stock disponible real
                        Forms\Components\Select::make('local_id')->label('Sale de (local/bodega)')->required()
                            ->options(fn (\Filament\Schemas\Components\Utilities\Get $get) => self::localesConStock($get('producto_id'), $get('variante_id')))
                            ->live()
                            ->helperText(fn (\Filament\Schemas\Components\Utilities\Get $get) => empty(self::localesConStock($get('producto_id'), $get('variante_id')))
                                ? 'Sin stock disponible para esta combinación. Elige otra o cambia el producto.'
                                : 'Se muestra el stock disponible de la combinación elegida (o del producto si no tiene variantes).')
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => filled($get('producto_id'))),

                        Forms\Components\TextInput::make('cantidad')->numeric()->default(1)->minValue(1)->required()->live(onBlur: true),
                        Forms\Components\TextInput::make('precio')->label('PVP (IVA incl.)')->numeric()->prefix('$')->required()->disabled()->dehydrated()->live(onBlur: true),

                        // ajuste de precio (descuento o adicional), igual que en pedidos
                        Forms\Components\Select::make('ajuste_modo')->label('Ajuste de precio')
                            ->options(['' => 'Sin ajuste', 'pct' => 'Porcentaje (%)', 'monto' => 'Valor ($)'])
                            ->default('')->live(),
                        Forms\Components\Select::make('ajuste_signo')->label('Sentido')
                            ->options(['mas' => 'Aumentar (+)', 'menos' => 'Descontar (−)'])->default('menos')->live()
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => in_array($get('ajuste_modo'), ['pct', 'monto'], true)),
                        Forms\Components\TextInput::make('descuento_pct')->label('Porcentaje')->numeric()->suffix('%')->default(0)->minValue(0)->maxValue(100)->live(onBlur: true)
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('ajuste_modo') === 'pct'),
                        Forms\Components\TextInput::make('valor_adicional')->label('Valor ($)')->numeric()->prefix('$')->default(0)->minValue(0)->live(onBlur: true)
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('ajuste_modo') === 'monto'),
                        Forms\Components\TextInput::make('motivo_adicional')->label('Motivo del ajuste')
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => in_array($get('ajuste_modo'), ['pct', 'monto'], true))
                            ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => in_array($get('ajuste_modo'), ['pct', 'monto'], true)),
                        Forms\Components\FileUpload::make('foto_adicional')->label('Foto (motivo)')->image()->directory('venta-stock')->disk('public')
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => in_array($get('ajuste_modo'), ['pct', 'monto'], true)),

                        Forms\Components\Hidden::make('iva_rate')->default(15),
                    ]),
            ]),

            \Filament\Schemas\Components\Section::make('Entrega')->columns(2)->schema([
                Forms\Components\Radio::make('modalidad')->label('Modalidad de entrega')->required()->default('retiro_local')->live()
                    ->options(['retiro_local' => 'Retiro en el local', 'transportista' => 'Envío a domicilio']),
                Forms\Components\TextInput::make('direccion_entrega')->label('Dirección de entrega')
                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('modalidad') === 'transportista')
                    ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('modalidad') === 'transportista'),
                Forms\Components\TextInput::make('celular_entrega')->label('Celular de contacto')
                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('modalidad') === 'transportista'),
            ]),

            \Filament\Schemas\Components\Section::make('Comprobante')->columns(2)->schema([
                Forms\Components\Radio::make('tipo_comprobante')->label('Tipo')->required()->default('factura')
                    ->options(['factura' => 'Factura (electrónica, SRI)', 'nota_venta' => 'Nota de venta (interna)']),
                Forms\Components\Textarea::make('info_adicional')->label('Información adicional (opcional)')->rows(2),
            ]),

            \Filament\Schemas\Components\Section::make('Formas de pago')->schema([
                Forms\Components\Repeater::make('pagos')->label('Pagos')
                    ->minItems(1)->defaultItems(1)->addActionLabel('Agregar pago')->columns(3)
                    ->schema([
                        Forms\Components\Select::make('metodo')->label('Método')->required()->live()
                            ->options(['efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'deposito' => 'Depósito', 'cheque' => 'Cheque', 'tarjeta' => 'Tarjeta']),
                        Forms\Components\TextInput::make('monto')->numeric()->prefix('$')->required(),
                        Forms\Components\Select::make('tarjeta_naturaleza')->label('Tipo de tarjeta')
                            ->options(['debito' => 'Débito', 'credito' => 'Crédito'])
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('metodo') === 'tarjeta')
                            ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('metodo') === 'tarjeta'),
                        Forms\Components\TextInput::make('nro_comprobante')->label('N° comprobante')
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => in_array($get('metodo'), ['transferencia', 'deposito'], true)),
                        Forms\Components\TextInput::make('cheque_girador')->label('Girador')
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('metodo') === 'cheque'),
                        Forms\Components\TextInput::make('cheque_numero')->label('N° cheque')
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('metodo') === 'cheque'),
                        Forms\Components\TextInput::make('cheque_banco')->label('Banco')
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('metodo') === 'cheque'),
                        Forms\Components\DatePicker::make('cheque_fecha_cobro')->label('Fecha de cobro')
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('metodo') === 'cheque')
                            ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('metodo') === 'cheque'),
                        Forms\Components\TextInput::make('pagador_nombre')->label('Nombre de quien paga (si es distinto al cliente)'),
                        Forms\Components\FileUpload::make('comprobantes')->label('Comprobante (foto, opcional)')->image()->multiple()
                            ->directory('comprobantes')->disk('public')->maxFiles(5)->columnSpanFull(),
                    ]),
            ]),
        ])->statePath('data');
    }

    /** locales con su stock disponible. Si hay variante_id, filtra por la COMBINACIÓN exacta;
     *  si no, suma el stock general del producto (sin variante). */
    protected static function localesConStock($pid, $varianteId = null): array
    {
        if (! $pid) return [];
        $out = [];
        foreach (Local::orderBy('nombre')->get() as $l) {
            $q = DB::table('stock')->where('local_id', $l->id);
            if ($varianteId) {
                $q->where('variante_id', $varianteId);
            } else {
                $q->where('producto_id', $pid)->whereNull('variante_id');
            }
            $cant = (int) $q->sum('cantidad');
            if ($cant > 0) $out[$l->id] = $l->nombre . ' — ' . $cant . ' disponible(s)';
        }
        return $out;
    }

    /** Solo productos con stock > 0 en al menos un local (general o de alguna variante). */
    protected static function productosConStock(): array
    {
        $conStockGeneral = DB::table('stock')->whereNull('variante_id')->where('cantidad', '>', 0)->pluck('producto_id');
        $varianteIds = DB::table('stock')->whereNotNull('variante_id')->where('cantidad', '>', 0)->pluck('variante_id');
        $conStockVariante = \App\Models\Variante::whereIn('id', $varianteIds)->pluck('producto_id');
        $ids = $conStockGeneral->merge($conStockVariante)->unique();
        return Producto::where('activo', true)->whereIn('id', $ids)->orderBy('nombre')->pluck('nombre', 'id')->all();
    }

    protected static function prodTieneAttr($pid, int $aid): bool
    {
        if (! $pid) return false;
        foreach (Variante::where('producto_id', $pid)->get() as $v) {
            $o = (array) ($v->opciones ?: []);
            if (array_key_exists($aid, $o) && filled($o[$aid])) return true;
        }
        return false;
    }

    protected static function opcionesProducto($pid, int $aid): array
    {
        if (! $pid) return [];
        $usadas = [];
        foreach (Variante::where('producto_id', $pid)->get() as $v) {
            $o = (array) ($v->opciones ?: []);
            if (isset($o[$aid]) && filled($o[$aid])) $usadas[(int) $o[$aid]] = true;
        }
        if (! $usadas) return [];
        return \App\Models\AtributoOpcion::whereIn('id', array_keys($usadas))->pluck('valor', 'id')->all();
    }

    protected static function resolverVariante($get, $set): void
    {
        $pid = $get('producto_id');
        if (! $pid) return;
        $sel = [];
        foreach ([1, 2, 3] as $aid) { $val = $get('atr_' . $aid); if (filled($val)) $sel[$aid] = (int) $val; }
        if (! $sel) return;
        foreach (Variante::where('producto_id', $pid)->get() as $v) {
            $op = [];
            foreach ((array) ($v->opciones ?: []) as $aid => $oid) { if (filled($oid)) $op[(int) $aid] = (int) $oid; }
            $coincide = true;
            foreach ($sel as $aid => $oid) { if (($op[$aid] ?? null) !== $oid) { $coincide = false; break; } }
            if ($coincide && count($op) === count($sel)) {
                $set('variante_id', $v->id);
                $set('precio', $v->pvp_final);
                return;
            }
        }
    }

    /** Resumen en vivo, aplicando el ajuste de precio (igual que en pedidos). */
    public function getResumen(): array
    {
        $items = $this->data['items'] ?? [];
        $total = 0.0;
        foreach ($items as $it) {
            $total += self::precioConAjuste($it) * max(1, (int) ($it['cantidad'] ?? 1));
        }
        $pagos = $this->data['pagos'] ?? [];
        $totalPagado = 0.0;
        foreach ($pagos as $p) $totalPagado += (float) ($p['monto'] ?? 0);
        return ['total' => round($total, 2), 'pagado' => round($totalPagado, 2), 'diff' => round($total - $totalPagado, 2)];
    }

    protected static function precioConAjuste(array $it): float
    {
        $pvp = (float) ($it['precio'] ?? 0);
        $modo = $it['ajuste_modo'] ?? '';
        $signo = ($it['ajuste_signo'] ?? 'menos') === 'mas' ? 1 : -1;
        $pct = $modo === 'pct' ? (float) ($it['descuento_pct'] ?? 0) : 0;
        $monto = $modo === 'monto' ? (float) ($it['valor_adicional'] ?? 0) : 0;
        $unit = $modo === 'pct' ? round($pvp + $signo * ($pvp * $pct / 100), 2) : round($pvp + $signo * $monto, 2);
        return max(0, $unit);
    }

    public function vender()
    {
        $data = $this->form->getState();
        $items = $data['items'] ?? [];
        if (! $items) { Notification::make()->danger()->title('Agrega al menos un producto')->send(); return; }

        // validar stock disponible por local antes de vender
        foreach ($items as $it) {
            if (empty($it['local_id'])) { Notification::make()->danger()->title('Elige el local/bodega de origen de cada producto')->send(); return; }
            $qDisp = DB::table('stock')->where('local_id', $it['local_id']);
            if (! empty($it['variante_id'])) { $qDisp->where('variante_id', $it['variante_id']); } else { $qDisp->where('producto_id', $it['producto_id'])->whereNull('variante_id'); }
            $disp = (int) $qDisp->sum('cantidad');
            $cant = (int) ($it['cantidad'] ?? 1);
            if ($cant > $disp) {
                $nombreProd = Producto::find($it['producto_id'])?->nombre ?? 'Producto';
                Notification::make()->danger()->title('Stock insuficiente')->body($nombreProd . ': pides ' . $cant . ', hay ' . $disp . ' disponible(s) en ese local.')->send();
                return;
            }
        }

        $resumen = $this->getResumen();
        if (abs($resumen['diff']) > 0.01) {
            Notification::make()->danger()->title('Los pagos no cuadran con el total')->body('Total: $' . number_format($resumen['total'], 2) . ' · Pagado: $' . number_format($resumen['pagado'], 2))->send();
            return;
        }

        $itemsVenta = [];
        foreach ($items as $it) {
            $p = Producto::find($it['producto_id']);
            $varLabel = null;
            if (! empty($it['variante_id']) && ($vv = Variante::find($it['variante_id']))) {
                $varLabel = $vv->combo_label ?: ($vv->nombre ?: null);
            }
            $precioFinal = self::precioConAjuste($it);
            $nombreCompleto = ($p?->nombre ?? 'Producto') . ($varLabel ? ' — ' . $varLabel : '');
            $itemsVenta[] = [
                'producto_id' => $it['producto_id'],
                'nombre' => $nombreCompleto,
                'cantidad' => (float) ($it['cantidad'] ?? 1),
                'precio_unitario_con_iva' => $precioFinal,
                'iva_rate' => (float) ($it['iva_rate'] ?? 15),
                'local_id' => $it['local_id'],
            ];
        }

        $r = \App\Services\Sri\VentaDirecta::emitir([
            'cliente_id' => $data['cliente_id'],
            'items' => $itemsVenta,
            'tipo' => $data['tipo_comprobante'] ?? 'factura',
            'forma_pago' => '01',
            'pagos_captura' => $data['pagos'] ?? [],
            'info_adicional' => $data['info_adicional'] ?? null,
            'local_id' => $itemsVenta[0]['local_id'] ?? (auth()->user()->local_id ?? null),
        ]);

        if (! ($r['ok'] ?? false)) {
            Notification::make()->danger()->title('No se pudo completar la venta')->body($r['msg'] ?? 'Error desconocido.')->persistent()->send();
            return;
        }

        // descontar stock del local correcto (de la VARIANTE exacta si aplica)
        foreach ($items as $idx => $itForm) {
            $it = $itemsVenta[$idx];
            MovimientoStock::create([
                'producto_id' => $it['producto_id'],
                'variante_id' => $itForm['variante_id'] ?? null,
                'local_id' => $it['local_id'],
                'tipo' => 'salida', 'cantidad' => (int) $it['cantidad'],
                'referencia' => $r['numero'] ?? 'venta-directa', 'nota' => 'Venta directa de stock',
            ]);
        }

        // crear despacho para que el colaborador siga el proceso de entrega/retiro
        $modalidad = $data['modalidad'] ?? 'retiro_local';
        $localOrigen = $itemsVenta[0]['local_id'] ?? null;
        $folioDes = \App\Services\Folios::next('DES');
        $notasEntrega = 'Venta directa · ' . ($r['numero'] ?? '');
        if ($modalidad === 'transportista') {
            $notasEntrega .= ' · Dirección: ' . ($data['direccion_entrega'] ?? '—') . ' · Celular: ' . ($data['celular_entrega'] ?? '—');
        }
        $detalle = collect($itemsVenta)->map(fn ($it) => ['nombre' => $it['nombre'], 'cantidad' => $it['cantidad']])->all();
        DB::table('despachos')->insert([
            'venta_id' => $r['venta_id'] ?? null, 'pedido_id' => null,
            'tipo' => 'venta_directa',
            'ruta' => $modalidad, 'estado' => 'programado', 'listo' => true,
            'folio' => $folioDes,
            'local_retiro_id' => $modalidad === 'retiro_local' ? $localOrigen : null,
            'local_destino_id' => $modalidad === 'retiro_local' ? $localOrigen : null,
            'notas' => $notasEntrega,
            'detalle_json' => json_encode($detalle),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        Notification::make()->success()->title('Venta registrada')->body('Comprobante ' . ($r['numero'] ?? '') . '. Despacho ' . $folioDes . ' creado para seguimiento.')->send();

        return redirect(\App\Filament\Resources\VentaResource::getUrl());
    }
}
