<?php

namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;

use App\Support\Acl;
use App\Filament\Resources\CompraResource\Pages;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\Variante;
use App\Models\Local;
use App\Models\Proveedor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

/**
 * Abastecimiento de stock: Compra a proveedor (producto terminado externo)
 * u Orden de producción (fabricación en taller propio).
 * Es una vía SEPARADA de Pedidos (venta a cliente) y Venta de stock.
 */
class CompraResource extends Resource
{
    protected static ?string $model = Compra::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-truck';
    protected static string|\UnitEnum|null $navigationGroup = 'Compras';
    protected static ?string $modelLabel = 'Compra / Producción';
    protected static ?string $pluralModelLabel = 'Compras y producción';
    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return Acl::ve(static::class);
    }
    public static function shouldRegisterNavigation(): bool
    {
        return Acl::rol() !== 'produccion';
    }

    public static function canCreate(): bool
    {
        // Solo quien gestiona el abastecimiento (dueño, operaciones, compras) puede CREAR órdenes.
        // Producción solo ejecuta las que ya le asignaron, nunca crea ni elige proveedor/taller.
        return Acl::puedeGestionarCompraProveedor();
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $q = parent::getEloquentQuery()->with(['proveedor', 'localDestino']);
        // Producción ve sus órdenes de fabricación asignadas (no compras a proveedor, no las crea).
        if (Acl::esProduccion()) {
            $q->where('tipo', 'produccion_interna');
        }
        return $q;
    }

    public static function getNavigationBadge(): ?string
    {
        $n = Compra::whereNotIn('estado', ['recibida', 'anulada'])->count();
        return $n > 0 ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Tipo y destino')->columns(2)->columnSpanFull()->schema([
                Forms\Components\Select::make('tipo')->label('¿Quién lo fabrica/provee?')->required()->live()
                    ->options(['proveedor' => 'Compra a proveedor (producto terminado)', 'produccion_interna' => 'Mi taller (producción interna)'])
                    ->disabled(fn (string $operation) => $operation === 'edit'),
                Forms\Components\Select::make('local_destino_id')->label('Local/bodega destino')->required()
                    ->options(fn () => Local::orderBy('nombre')->pluck('nombre', 'id')),
                Forms\Components\Select::make('proveedor_id')->label('Proveedor')
                    ->options(fn () => Proveedor::where('activo', true)->pluck('nombre', 'id'))
                    ->searchable()
                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tipo') === 'proveedor')
                    ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tipo') === 'proveedor'),
            ]),

            \Filament\Schemas\Components\Section::make('Productos')->columnSpanFull()->schema([
                Forms\Components\Repeater::make('items')->relationship()->label('Ítems')
                    ->minItems(1)->defaultItems(1)->addActionLabel('Agregar producto')->columns(3)
                    ->schema([
                        Forms\Components\Select::make('producto_id')->label('Producto')->required()->columnSpanFull()
                            ->options(fn () => Producto::orderBy('nombre')->pluck('nombre', 'id'))
                            ->searchable()->live()
                            ->afterStateUpdated(function ($state, \Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get) {
                                $set('variante_id', null);
                                $set('foto_modelo_preview', null);
                                if ($state && ($p = Producto::find($state))) {
                                    $set('nombre', $p->nombre);
                                    $tipo = $get('../../tipo');
                                    $set('costo_unitario', (float) ($tipo === 'proveedor' ? ($p->costo_proveedor ?: 0) : ($p->costo_produccion ?: 0)));
                                    $set('foto_modelo_preview', $p->imagen_principal);
                                }
                            }),

                        \Filament\Schemas\Components\Fieldset::make('Combinación (opcional)')->columns(3)->columnSpanFull()->schema([
                            Forms\Components\Select::make('atr_1')->live()->dehydrated(false)->label('Tapiz')
                                ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => self::prodTieneAttr($get('producto_id'), 1))
                                ->options(fn (\Filament\Schemas\Components\Utilities\Get $get) => self::opcionesProducto($get('producto_id'), 1))
                                ->live()->afterStateUpdated(fn (\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set) => self::resolverVariante($get, $set)),
                            Forms\Components\Select::make('atr_2')->live()->dehydrated(false)->label('Lado')
                                ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => self::prodTieneAttr($get('producto_id'), 2))
                                ->options(fn (\Filament\Schemas\Components\Utilities\Get $get) => self::opcionesProducto($get('producto_id'), 2))
                                ->live()->afterStateUpdated(fn (\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set) => self::resolverVariante($get, $set)),
                            Forms\Components\Select::make('atr_3')->live()->dehydrated(false)->label('Madera')
                                ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => self::prodTieneAttr($get('producto_id'), 3))
                                ->options(fn (\Filament\Schemas\Components\Utilities\Get $get) => self::opcionesProducto($get('producto_id'), 3))
                                ->live()->afterStateUpdated(fn (\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set) => self::resolverVariante($get, $set)),
                        ])->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => filled($get('producto_id'))),

                        Forms\Components\Hidden::make('variante_id'),
                        Forms\Components\Hidden::make('nombre'),
                        Forms\Components\Hidden::make('foto_modelo_preview')->dehydrated(false),

                        Forms\Components\Placeholder::make('foto_actual')->label('Foto actual')
                            ->content(function (\Filament\Schemas\Components\Utilities\Get $get) {
                                $vid = $get('variante_id');
                                if ($vid && ($v = Variante::find($vid)) && $v->foto) {
                                    return new \Illuminate\Support\HtmlString('<img src="' . $v->foto_url . '" style="width:64px;height:64px;object-fit:cover;border-radius:8px;border:1px solid #e4e8ec;">');
                                }
                                $fm = $get('foto_modelo_preview');
                                if ($fm) return new \Illuminate\Support\HtmlString('<img src="' . \Illuminate\Support\Facades\Storage::disk('public')->url($fm) . '" style="width:64px;height:64px;object-fit:cover;border-radius:8px;border:1px solid #e4e8ec;">');
                                return 'Sin foto aún.';
                            })
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => filled($get('producto_id')))->columnSpan(1),

                        Forms\Components\FileUpload::make('foto_nueva')->dehydrated(false)->label('Actualizar foto')
                            ->image()->directory('variantes')->disk('public')->avatar()
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => filled($get('variante_id')))
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('cantidad')->numeric()->default(1)->minValue(1)->required()->columnSpan(1),
                        Forms\Components\TextInput::make('costo_unitario')->label('Costo (sin IVA)')->numeric()->prefix('$')->required()->columnSpan(2),
                        Forms\Components\Hidden::make('iva_rate')->default(fn () => \App\Support\Impuestos::ivaVigente()),

                        \Filament\Schemas\Components\Fieldset::make('Detalles y acabados')->columns(4)->columnSpanFull()->schema([
                            Forms\Components\TextInput::make('tapiz_principal')->label('Tapiz principal'),
                            Forms\Components\FileUpload::make('foto_tapiz_principal')->label('Foto')->image()->directory('compra-items')->disk('public')->imagePreviewHeight('60')->panelLayout('compact'),
                            Forms\Components\TextInput::make('tapiz_secundario')->label('Tapiz secundario'),
                            Forms\Components\FileUpload::make('foto_tapiz_secundario')->label('Foto')->image()->directory('compra-items')->disk('public')->imagePreviewHeight('60')->panelLayout('compact'),
                            Forms\Components\TextInput::make('cojines')->label('Cojines alternos'),
                            Forms\Components\FileUpload::make('foto_cojines')->label('Foto')->image()->directory('compra-items')->disk('public')->imagePreviewHeight('60')->panelLayout('compact'),
                            Forms\Components\TextInput::make('lacado')->label('Lacado'),
                            Forms\Components\FileUpload::make('foto_lacado')->label('Foto')->image()->directory('compra-items')->disk('public')->imagePreviewHeight('60')->panelLayout('compact'),
                            Forms\Components\Textarea::make('notas_adicionales')->label('Notas')->rows(2)->columnSpanFull(),
                        ]),
                    ]),
            ]),

            \Filament\Schemas\Components\Section::make('Documento del proveedor (opcional)')->columns(3)->columnSpanFull()
                ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tipo') === 'proveedor')
                ->schema([
                    Forms\Components\Select::make('doc_tipo')->label('Tipo de documento')
                        ->options([
                            'factura' => 'Factura',
                            'nota_venta' => 'Nota de venta',
                            'liquidacion' => 'Liquidación de compra',
                            'nota_credito' => 'Nota de crédito',
                            'reembolso' => 'Reembolso',
                            'ninguno' => 'Sin documento',
                        ])->native(false),
                    Forms\Components\TextInput::make('doc_numero')->label('N° de documento'),
                    Forms\Components\DatePicker::make('doc_fecha')->label('Fecha del documento'),
                    Forms\Components\Select::make('sustento_tributario')->label('Sustento tributario (ATS)')
                        ->options([
                            '01' => '01 · Crédito tributario para IVA',
                            '02' => '02 · Costo o gasto',
                            '03' => '03 · Activo fijo',
                            '07' => '07 · No genera crédito tributario',
                            '08' => '08 · Gasto por reembolso',
                        ])->native(false)->helperText('Para el Anexo Transaccional. Opcional si aún no declaras ATS.'),
                    Forms\Components\TextInput::make('autorizacion_sri')->label('N° autorización SRI')->maxLength(60),
                ]),

            \Filament\Schemas\Components\Section::make('Retenciones emitidas (opcional)')->columns(4)->collapsed()->columnSpanFull()
                ->description('Solo si eres agente de retención. Déjalo vacío si no aplica.')
                ->schema([
                    Forms\Components\TextInput::make('ret_iva')->label('Retención IVA')->numeric()->prefix('$')->default(0),
                    Forms\Components\TextInput::make('ret_renta')->label('Retención Renta')->numeric()->prefix('$')->default(0),
                    Forms\Components\TextInput::make('ret_comprobante')->label('N° comprobante retención')->maxLength(30),
                    Forms\Components\DatePicker::make('ret_fecha')->label('Fecha retención'),
                ]),

            \Filament\Schemas\Components\Section::make('Notas')->columnSpanFull()->schema([
                Forms\Components\Textarea::make('notas')->label('Notas adicionales')->rows(2),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('folio')->label('Folio')->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('tipo')->label('Tipo')->badge()
                    ->formatStateUsing(fn ($state) => $state === 'proveedor' ? 'Proveedor' : 'Producción')
                    ->color(fn ($state) => $state === 'proveedor' ? 'info' : 'primary'),
                Tables\Columns\TextColumn::make('proveedor.nombre')->label('Proveedor')->placeholder('—'),
                Tables\Columns\TextColumn::make('localDestino.nombre')->label('Destino')->placeholder('—'),
                Tables\Columns\TextColumn::make('estado')->label('Estado')->badge()
                    ->formatStateUsing(fn ($state) => [
                        'creada' => 'Creada', 'en_proceso' => 'En proceso', 'listo_envio' => 'Listo para enviar',
                        'en_transito' => 'En tránsito', 'recibida' => 'Recibida', 'anulada' => 'Anulada',
                    ][$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'creada' => 'gray', 'en_proceso' => 'warning', 'listo_envio' => 'info',
                        'en_transito' => 'primary', 'recibida' => 'success', 'anulada' => 'danger', default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('total')->money('usd')->sortable()
                    ->visible(fn () => ! \App\Support\Acl::esProduccion()),
                Tables\Columns\TextColumn::make('created_at')->label('Fecha')->date('d/m/Y')->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')->options(['proveedor' => 'Proveedor', 'produccion_interna' => 'Producción']),
                Tables\Filters\SelectFilter::make('estado')->options([
                    'creada' => 'Creada', 'en_proceso' => 'En proceso', 'listo_envio' => 'Listo para enviar',
                    'en_transito' => 'En tránsito', 'recibida' => 'Recibida', 'anulada' => 'Anulada',
                ]),
            ])
            ->recordUrl(fn (Compra $r) => Pages\ViewCompra::getUrl(['record' => $r->id]))
            ->actions([
                Actions\Action::make('verDetalle')->label('Ver detalle')->icon('heroicon-o-eye')
                    ->modalHeading(fn (Compra $r) => 'Orden ' . ($r->folio ?: $r->id))
                    ->modalContent(fn (Compra $r) => view('filament.compras.detalle-modal', ['c' => $r->load('items.producto')]))
                    ->modalSubmitAction(false)->modalCancelActionLabel('Cerrar'),

                Actions\Action::make('aProcesoLista')->label('Marcar en proceso')->icon('heroicon-o-cog-6-tooth')->color('warning')
                    ->visible(fn (Compra $r) => $r->estado === 'creada' && ($r->tipo === 'produccion_interna' ? \App\Support\Acl::puedeGestionarProduccionInterna() : \App\Support\Acl::puedeGestionarCompraProveedor()))
                    ->requiresConfirmation()
                    ->action(function (Compra $r) {
                        $r->update(['estado' => 'en_proceso']);
                        if ($r->tipo === 'produccion_interna') {
                            try {
                                $pdf = \App\Services\PdfErp::ordenCompra($r->fresh()->load('items.producto'));
                                $dest = \Illuminate\Support\Facades\DB::table('users')->whereIn('rol', ['produccion', 'operaciones', 'admin'])->where('activo', true)->pluck('email')->filter()->unique()->all();
                                $cuerpo = '<p>Nueva orden de producción asignada al taller:</p><p><strong>' . ($r->folio ?: '#'.$r->id) . '</strong></p>';
                                $html = \App\Support\CorreoBrand::wrap('Orden de producción · ' . ($r->folio ?: '#'.$r->id), $cuerpo);
                                foreach ($dest as $to) { try { \Illuminate\Support\Facades\Mail::to($to)->send(new \App\Mail\DocumentoPedido('Orden de producción · ' . ($r->folio ?: '#'.$r->id), $html, [$pdf])); } catch (\Throwable $e) { report($e); } }
                            } catch (\Throwable $e) { report($e); }
                        }
                    }),

                Actions\Action::make('solicitarMaterialLista')->label('Solicitar material')->icon('heroicon-o-inbox-arrow-down')->color('warning')
                    ->visible(fn (Compra $r) => $r->tipo === 'produccion_interna' && in_array($r->estado, ['creada', 'en_proceso'], true) && \App\Support\Acl::puedeGestionarProduccionInterna())
                    ->form(fn (Compra $r) => [
                        Forms\Components\Select::make('materia_prima_id')->label('Material')->required()->live()->searchable()
                            ->options(fn () => \App\Models\MateriaPrima::where('activo', true)->pluck('nombre', 'id'))
                            ->afterStateUpdated(function ($state, \Filament\Schemas\Components\Utilities\Set $set) use ($r) {
                                $need = \App\Services\Materiales::bomDeCompra($r);
                                if ($state && isset($need[$state])) $set('cantidad', round((float) $need[$state], 2));
                            }),
                        Forms\Components\Placeholder::make('requerido_info')->label('Lo que necesita el producto')
                            ->content(function (\Filament\Schemas\Components\Utilities\Get $get) use ($r) {
                                $mid = $get('materia_prima_id');
                                if (! $mid) return 'Elige un material para ver lo requerido (si hay ficha cargada).';
                                $need = \App\Services\Materiales::bomDeCompra($r);
                                $m = \App\Models\MateriaPrima::find($mid);
                                $u = $m->unidad ?? '';
                                if (! isset($need[$mid])) return 'Este material no está en la ficha. Disponible: ' . number_format((float) ($m->stock ?? 0), 2) . ' ' . $u . '. Ingresa la cantidad manualmente.';
                                return 'Requerido por la ficha: ' . number_format((float) $need[$mid], 2) . ' ' . $u . ' · En stock: ' . number_format((float) ($m->stock ?? 0), 2) . ' ' . $u;
                            }),
                        Forms\Components\TextInput::make('cantidad')->numeric()->required()->minValue(0.01)->helperText('Precargado según la ficha del producto. Puedes editarlo o ingresarlo manual si no hay ficha.'),
                        Forms\Components\Textarea::make('nota')->rows(2),
                    ])
                    ->action(function (Compra $r, array $data) {
                        \App\Models\MovimientoMaterial::create([
                            'materia_prima_id' => $data['materia_prima_id'], 'compra_id' => $r->id, 'pedido_id' => null,
                            'tipo' => 'solicitud', 'estado' => 'solicitado', 'cantidad' => $data['cantidad'],
                            'nota' => $data['nota'] ?? null, 'user_id' => auth()->id(),
                        ]);
                        if ($r->estado === 'creada') $r->update(['estado' => 'en_proceso']);
                        \Filament\Notifications\Notification::make()->success()->title('Material solicitado')->send();
                    }),

                Actions\Action::make('usarMaterialLista')->label('Registrar material usado')->icon('heroicon-o-minus-circle')->color('danger')
                    ->visible(fn (Compra $r) => $r->tipo === 'produccion_interna' && in_array($r->estado, ['creada', 'en_proceso'], true) && \App\Support\Acl::puedeGestionarProduccionInterna())
                    ->form([
                        Forms\Components\Select::make('materia_prima_id')->label('Material')->required()->live()->searchable()
                            ->options(fn () => \App\Models\MateriaPrima::where('activo', true)->pluck('nombre', 'id')),
                        Forms\Components\Placeholder::make('disp')->label('Disponible en bodega')
                            ->content(function (\Filament\Schemas\Components\Utilities\Get $get) {
                                $m = $get('materia_prima_id') ? \App\Models\MateriaPrima::find($get('materia_prima_id')) : null;
                                return $m ? (number_format((float) $m->stock, 2) . ' ' . $m->unidad) : '—';
                            }),
                        Forms\Components\TextInput::make('cantidad')->label('Cantidad REALMENTE usada')->numeric()->required()->minValue(0.01),
                        Forms\Components\Textarea::make('nota')->rows(2),
                    ])
                    ->action(function (Compra $r, array $data) {
                        $mp = \App\Models\MateriaPrima::find($data['materia_prima_id']);
                        $usar = (float) $data['cantidad'];
                        $disp = $mp ? (float) $mp->stock : 0;
                        \App\Models\MovimientoMaterial::create([
                            'materia_prima_id' => $data['materia_prima_id'], 'compra_id' => $r->id, 'pedido_id' => null,
                            'tipo' => 'uso', 'cantidad' => $usar, 'nota' => $data['nota'] ?? null, 'user_id' => auth()->id(),
                        ]);
                        if ($mp) { $mp->stock = max(0, $disp - $usar); $mp->save(); }
                        \Filament\Notifications\Notification::make()->success()->title('Material descontado')->send();
                    }),

                Actions\Action::make('devolverSobranteLista')->label('Devolver sobrante')->icon('heroicon-o-arrow-uturn-left')->color('gray')
                    ->visible(fn (Compra $r) => $r->tipo === 'produccion_interna' && in_array($r->estado, ['creada', 'en_proceso'], true) && \App\Support\Acl::puedeGestionarProduccionInterna())
                    ->modalHeading('Devolver material sobrante a bodega')
                    ->modalDescription('Registra lo que no se usó del material entregado, para que vuelva al stock.')
                    ->form([
                        Forms\Components\Select::make('materia_prima_id')->label('Material')->required()->searchable()
                            ->options(fn () => \App\Models\MateriaPrima::where('activo', true)->pluck('nombre', 'id')),
                        Forms\Components\TextInput::make('cantidad')->label('Cantidad a devolver')->numeric()->required()->minValue(0.01),
                        Forms\Components\Textarea::make('nota')->label('Motivo')->rows(2),
                    ])
                    ->action(function (Compra $r, array $data) {
                        \App\Models\MovimientoMaterial::create([
                            'materia_prima_id' => $data['materia_prima_id'], 'compra_id' => $r->id, 'pedido_id' => null,
                            'tipo' => 'devolucion', 'cantidad' => $data['cantidad'], 'nota' => $data['nota'] ?? null, 'user_id' => auth()->id(),
                        ]);
                        if (class_exists(\App\Models\Bitacora::class)) {
                            \App\Models\Bitacora::registrar('devolvió sobrante de material', 'Compra', $r->id, $r->folio ?: '');
                        }
                        \Filament\Notifications\Notification::make()->success()->title('Sobrante devuelto al stock')->send();
                    }),

                Actions\Action::make('listoLista')->label('Listo para enviar')->icon('heroicon-o-check-circle')->color('success')
                    ->visible(fn (Compra $r) => in_array($r->estado, ['creada', 'en_proceso'], true) && ($r->tipo === 'produccion_interna' ? \App\Support\Acl::puedeGestionarProduccionInterna() : \App\Support\Acl::puedeGestionarCompraProveedor()))
                    ->modalHeading('Confirmar bultos y marcar listo')
                    ->modalDescription('Confirma cuántos bultos ocupa cada producto. Se generan las etiquetas.')
                    ->fillForm(fn (Compra $r) => [
                        'bultos_items' => $r->items->map(fn ($it) => ['item_id' => $it->id, 'nombre' => $it->nombre, 'bultos' => max(1, (int) ($it->bultos ?? 1))])->all(),
                        'local_destino_id' => $r->local_destino_id,
                    ])
                    ->form([
                        Forms\Components\Repeater::make('bultos_items')->label('Bultos por producto')->schema([
                            Forms\Components\Hidden::make('item_id'),
                            Forms\Components\TextInput::make('nombre')->label('Producto')->disabled(),
                            Forms\Components\TextInput::make('bultos')->label('Bultos')->numeric()->minValue(1)->required(),
                        ])->columns(2)->disableItemCreation()->disableItemDeletion(),
                        Forms\Components\Select::make('local_destino_id')->label('Local/bodega destino')
                            ->options(fn () => \Illuminate\Support\Facades\DB::table('locales')->pluck('nombre', 'id'))
                            ->required()->searchable(),
                        Forms\Components\Select::make('empleado_receptor_id')->label('Empleado que recibe y valida')
                            ->options(fn () => \App\Models\User::where('activo', true)->pluck('name', 'id'))
                            ->required()->searchable(),
                    ])
                    ->action(function (Compra $r, array $data) {
                        foreach ($data['bultos_items'] ?? [] as $bi) {
                            \App\Models\CompraItem::where('id', $bi['item_id'])->where('compra_id', $r->id)->update(['bultos' => max(1, (int) $bi['bultos'])]);
                        }
                        $r->update(['estado' => 'listo_envio']);
                        $pdf = \App\Services\Etiquetas::generarParaCompra($r->fresh());

                        // auto-crear despacho de abastecimiento (sin paso manual aparte)
                        $folio = \App\Services\Folios::next('DES');
                        $detalle = $r->items->map(fn ($it) => ['nombre' => $it->nombre, 'cantidad' => $it->cantidad])->all();
                        \Illuminate\Support\Facades\DB::table('despachos')->insert([
                            'compra_id' => $r->id, 'pedido_id' => null,
                            'tipo' => 'abastecimiento',
                            'ruta' => 'transportista', 'estado' => 'programado', 'listo' => true,
                            'folio' => $folio,
                            'local_destino_id' => $data['local_destino_id'],
                            'empleado_receptor_id' => $data['empleado_receptor_id'],
                            'notas' => 'Abastecimiento · ' . ($r->tipo === 'proveedor' ? 'Compra a proveedor' : 'Producción interna') . ' · ' . ($r->folio ?: ''),
                            'detalle_json' => json_encode($detalle),
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                        if (class_exists(\App\Models\Bitacora::class)) {
                            \App\Models\Bitacora::registrar('generó despacho de abastecimiento', 'Compra', $r->id, ($r->folio ?: '') . ' · ' . $folio);
                        }
                        $empleado = \App\Models\User::find($data['empleado_receptor_id']);
                        if ($empleado && $empleado->email) {
                            try {
                                \Illuminate\Support\Facades\Mail::to($empleado->email)->send(new \App\Mail\DocumentoPedido(
                                    'Despacho asignado · ' . $folio,
                                    \App\Support\CorreoBrand::wrap('Despacho pendiente de recibir', '<p>Se te asignó el despacho <strong>' . $folio . '</strong> (abastecimiento). Revisa el módulo Despachos para validar la recepción.</p>'),
                                    []
                                ));
                            } catch (\Throwable $e) { report($e); }
                        }
                        \Filament\Notifications\Notification::make()->success()->title('Listo para enviar')->body('Etiquetas generadas. Despacho ' . $folio . ' creado, asignado a ' . ($empleado->name ?? '—') . '.')->send();
                    }),

                Actions\Action::make('descargarEtiquetas')->label('Descargar PDF')->icon('heroicon-o-arrow-down-tray')->color('gray')
                    ->visible(fn (Compra $r) => ! in_array($r->estado, ['creada', 'en_proceso'], true))
                    ->url(fn (Compra $r) => route('etiquetas.compra.descarga', $r))
                    ->openUrlInNewTab(),
                Actions\EditAction::make()->visible(fn (Compra $r) => $r->estado === 'creada' && ($r->tipo === 'produccion_interna' ? \App\Support\Acl::puedeGestionarProduccionInterna() : \App\Support\Acl::puedeGestionarCompraProveedor())),
            ]);
    }

    protected static function contarAttrsVisibles($pid): int
    {
        if (! $pid) return 0;
        return collect([1, 2, 3])->filter(fn ($aid) => self::prodTieneAttr($pid, $aid))->count();
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
            if ($coincide && count($op) === count($sel)) { $set('variante_id', $v->id); return; }
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompras::route('/'),
            'create' => Pages\CreateCompra::route('/create'),
            'view' => Pages\ViewCompra::route('/{record}'),
            'edit' => Pages\EditCompra::route('/{record}/edit'),
        ];
    }
}
