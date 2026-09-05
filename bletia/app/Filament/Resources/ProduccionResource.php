<?php
namespace App\Filament\Resources;
use Filament\Actions;

use App\Filament\Resources\ProduccionResource\Pages;
use App\Models\PedidoEspecial;
use App\Models\MateriaPrima;
use App\Models\MovimientoMaterial;
use App\Services\EstadoPedidoErp;
use App\Services\Materiales;
use App\Services\PdfErp;
use App\Services\Traza;
use App\Support\Acl;
use App\Support\CorreoBrand;
use App\Mail\DocumentoPedido;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ProduccionResource extends Resource
{
    protected static ?string $model = PedidoEspecial::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationLabel = 'Producción';
    protected static ?string $modelLabel = 'pedido en producción';
    protected static ?string $pluralModelLabel = 'pedidos en producción';
    protected static string|\UnitEnum|null $navigationGroup = 'Producción';

    public static function canViewAny(): bool { return Acl::ve(static::class); }
    public static function shouldRegisterNavigation(): bool { return Acl::rol() !== 'produccion'; }
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }

    public static function getEloquentQuery(): Builder
    {
        // solo pedidos asignados a producción interna y en proceso
        return parent::getEloquentQuery()
            ->with(['cliente'])
            ->where('destino_fab', 'interno')
            ->whereIn('estado_erp', ['en_produccion']);
    }

    public static function getNavigationBadge(): ?string
    {
        $n = PedidoEspecial::where('destino_fab', 'interno')->where('estado_erp', 'en_produccion')->count();
        return $n ? (string) $n : null;
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('folio')->label('Folio')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('cliente.nombre')->label('Cliente')->placeholder('—'),
            Tables\Columns\TextColumn::make('fecha_comprometida')->label('Entrega')->date('d/m/Y')->placeholder('—')->sortable(),
            Tables\Columns\TextColumn::make('items_count')->label('Ítems')->counts('items')->badge(),
            Tables\Columns\TextColumn::make('estado_erp')->label('Estado')->badge()->color('taller')
                ->formatStateUsing(fn ($state) => EstadoPedidoErp::ESTADOS[$state] ?? $state),
        ])
        ->actions([
            // ver detalle del pedido (ítems, specs, fotos)
            Actions\Action::make('detalle')->label('Ver detalle')->icon('heroicon-o-eye')
                ->modalHeading(fn (PedidoEspecial $r) => 'Pedido ' . ($r->folio ?: $r->id))
                ->modalContent(fn (PedidoEspecial $r) => view('filament.produccion.detalle', ['pedido' => $r->load('items')]))
                ->modalSubmitAction(false)->modalCancelActionLabel('Cerrar'),

            // aceptar orden de fabricación -> notifica partes + PDF completo + etiquetas
            Actions\Action::make('aceptar_of')->label('Aceptar orden de fabricación')->icon('heroicon-o-clipboard-document-check')->color('taller')
                ->visible(fn (PedidoEspecial $r) => empty($r->of_aceptada_at))
                ->requiresConfirmation()
                ->action(function (PedidoEspecial $record) {
                    DB::table('pedidos')->where('id', $record->id)->update(['of_aceptada_at' => now(), 'of_aceptada_por' => auth()->id()]);
                    Traza::registrar($record, 'of_aceptada', 'Taller aceptó la orden de fabricación');
                    $orden = PdfErp::ordenCompleta($record);
                    $etiq  = PdfErp::etiquetasBultos($record);
                    $dest = DB::table('users')->whereIn('rol', ['operaciones', 'admin'])->where('activo', true)->pluck('email')->all();
                    if ($record->vendedor_id) { $ve = DB::table('users')->where('id', $record->vendedor_id)->value('email'); if ($ve) $dest[] = $ve; }
                    if ($du = EstadoPedidoErp::dueno()) $dest[] = $du;
                    $dest = collect($dest)->filter()->unique()->all();
                    $cuerpo = '<p>El taller <strong>aceptó la orden de fabricación</strong> del pedido <strong>' . ($record->folio ?: $record->id) . '</strong>.</p>'
                        . '<p>Adjuntamos la orden completa (especificaciones, variables y fotos) y las etiquetas de bultos.</p>';
                    $html = CorreoBrand::wrap('Orden de fabricación aceptada', $cuerpo);
                    foreach ($dest as $to) { try { Mail::to($to)->send(new DocumentoPedido('OF aceptada · ' . ($record->folio ?: $record->id), $html, [$orden, $etiq])); } catch (\Throwable $e) { report($e); } }
                    \Filament\Notifications\Notification::make()->success()->title('Orden aceptada')->body('Se notificó a las partes y se generó la orden + etiquetas.')->send();
                }),

            // solicitar material (registro + notifica operaciones/bodega)
            Actions\Action::make('solicitar')->label('Solicitar material')->icon('heroicon-o-inbox-arrow-down')->color('warning')
                ->form(fn (PedidoEspecial $record) => [ // V_SOLIC_BOM
                    Forms\Components\Select::make('materia_prima_id')->label('Material')->required()->live()
                        ->options(fn () => MateriaPrima::where('activo', true)->orderBy('nombre')->pluck('nombre', 'id'))
                        ->afterStateUpdated(function ($state, \Filament\Schemas\Components\Utilities\Set $set) use ($record) {
                            $need = \App\Services\Materiales::bomDePedido($record);
                            if ($state && isset($need[$state])) $set('cantidad', round((float) $need[$state], 2));
                        }),
                    Forms\Components\Placeholder::make('requerido_info')->label('Lo que necesita el mueble')
                        ->content(function (\Filament\Schemas\Components\Utilities\Get $get) use ($record) {
                            $mid = $get('materia_prima_id');
                            if (! $mid) return 'Elige un material para ver lo requerido.';
                            $need = \App\Services\Materiales::bomDePedido($record);
                            $m = MateriaPrima::find($mid);
                            $u = $m->unidad ?? '';
                            if (! isset($need[$mid])) return 'Este material no está en la ficha del mueble. Disponible: ' . number_format((float) ($m->stock ?? 0), 2) . ' ' . $u;
                            return 'Requerido por el mueble: ' . number_format((float) $need[$mid], 2) . ' ' . $u . ' · En stock: ' . number_format((float) ($m->stock ?? 0), 2) . ' ' . $u;
                        }),
                    Forms\Components\TextInput::make('cantidad')->numeric()->required()->minValue(0.01)->live(onBlur: true)
                        ->helperText(function (\Filament\Schemas\Components\Utilities\Get $get) use ($record) {
                            $mid = $get('materia_prima_id'); $cant = (float) $get('cantidad');
                            if (! $mid || $cant <= 0) return null;
                            $need = \App\Services\Materiales::bomDePedido($record);
                            if (! isset($need[$mid])) return null;
                            $req = (float) $need[$mid]; $dif = round($cant - $req, 2);
                            $m = MateriaPrima::find($mid); $u = $m->unidad ?? '';
                            if ($dif > 0) return '⚠ Estás pidiendo ' . number_format($dif, 2) . ' ' . $u . ' de MÁS de lo que necesita el mueble (' . number_format($req, 2) . ' ' . $u . ').';
                            if ($dif < 0) return '⚠ Estás pidiendo ' . number_format(abs($dif), 2) . ' ' . $u . ' de MENOS de lo que necesita el mueble (' . number_format($req, 2) . ' ' . $u . ').';
                            return '✓ Coincide con lo que necesita el mueble.';
                        }),
                    Forms\Components\Textarea::make('nota')->rows(2),
                ])
                ->action(function (PedidoEspecial $record, array $data) {
                    MovimientoMaterial::create([
                        'materia_prima_id' => $data['materia_prima_id'], 'pedido_id' => $record->id,
                        'tipo' => 'solicitud', 'estado' => 'solicitado', 'cantidad' => $data['cantidad'],
                        'nota' => $data['nota'] ?? null, 'user_id' => auth()->id(),
                    ]);
                    // notificar operaciones + bodega
                    $mp = MateriaPrima::find($data['materia_prima_id']);
                    $dest = DB::table('users')->whereIn('rol', ['operaciones', 'bodega', 'admin'])->where('activo', true)->pluck('email')->filter()->unique()->all();
                    $cuerpo = '<p>Producción solicita material para el pedido <strong>' . ($record->folio ?: $record->id) . '</strong>:</p>'
                        . '<p><strong>' . e($mp->nombre ?? '') . '</strong>: ' . $data['cantidad'] . ' ' . ($mp->unidad ?? '') . '</p>'
                        . ($data['nota'] ?? null ? '<p>Nota: ' . e($data['nota']) . '</p>' : '');
                    $html = CorreoBrand::wrap('Solicitud de material', $cuerpo);
                    foreach ($dest as $to) { try { Mail::to($to)->send(new DocumentoPedido('Solicitud de material · ' . ($record->folio ?: $record->id), $html, [])); } catch (\Throwable $e) { report($e); } }
                    \Filament\Notifications\Notification::make()->success()->title('Material solicitado')->body('Se notificó a operaciones y bodega.')->send();
                }),

            // registrar material usado (descuenta stock)
            Actions\Action::make('usar')->label('Registrar material usado')->icon('heroicon-o-minus-circle')->color('danger')
                ->form([
                    Forms\Components\Select::make('materia_prima_id')->label('Material')->required()->live()
                        ->options(fn () => MateriaPrima::where('activo', true)->orderBy('nombre')->pluck('nombre', 'id')),
                    Forms\Components\Placeholder::make('disp')->label('Disponible')
                        ->content(function (\Filament\Schemas\Components\Utilities\Get $get) {
                            $m = $get('materia_prima_id') ? MateriaPrima::find($get('materia_prima_id')) : null;
                            return $m ? (number_format((float) $m->stock, 2) . ' ' . $m->unidad) : '—';
                        }),
                    Forms\Components\TextInput::make('cantidad')->numeric()->required()->minValue(0.01),
                    Forms\Components\Textarea::make('nota')->rows(2),
                ])
                ->action(function (PedidoEspecial $record, array $data) {
                    // V_MAT_2B
                    $mp = MateriaPrima::find($data['materia_prima_id']);
                    $usar = (float) $data['cantidad'];
                    $disp = $mp ? (float) $mp->stock : 0;
                    MovimientoMaterial::create([
                        'materia_prima_id' => $data['materia_prima_id'], 'pedido_id' => $record->id,
                        'tipo' => 'uso', 'cantidad' => $usar, 'nota' => $data['nota'] ?? null, 'user_id' => auth()->id(),
                    ]);
                    if ($mp) { $mp->stock = max(0, $disp - $usar); $mp->save(); }
                    if ($mp && $usar > $disp) {
                        Materiales::alarmaFaltante($record, [['materia' => $mp->nombre, 'unidad' => $mp->unidad, 'requiere' => $usar, 'disponible' => $disp, 'falta' => round($usar - $disp, 3)]], 'uso en taller');
                        \Filament\Notifications\Notification::make()->danger()->title('Material insuficiente')->body('Se avisó a Operaciones y Dueño.')->send();
                    } else {
                        \Filament\Notifications\Notification::make()->success()->title('Material descontado')->send();
                    }
                }),

            // listo para despacho
            Actions\Action::make('listo')->label('Listo para despacho')->icon('heroicon-o-check-circle')->color('success')
                ->modalHeading('Confirmar bultos y marcar listo')
                ->modalDescription('Confirma cuántos bultos (paquetes) ocupa cada producto. Se generarán las etiquetas.')
                ->modalSubmitActionLabel('Confirmar y marcar listo')
                ->fillForm(function (PedidoEspecial $record) {
                    $items = \Illuminate\Support\Facades\DB::table('pedido_items')->where('pedido_id', $record->id)->get();
                    return ['bultos_items' => $items->map(fn ($it) => ['item_id' => $it->id, 'nombre' => $it->nombre, 'bultos' => max(1, (int) ($it->bultos ?? 1))])->all()];
                })
                ->form([
                    \Filament\Forms\Components\Repeater::make('bultos_items')->label('Bultos por producto')
                        ->schema([
                            \Filament\Forms\Components\Hidden::make('item_id'),
                            \Filament\Forms\Components\TextInput::make('nombre')->label('Producto')->disabled()->dehydrated(false)->columnSpan(2),
                            \Filament\Forms\Components\TextInput::make('bultos')->label('Bultos')->numeric()->minValue(1)->default(1)->required(),
                        ])->columns(3)->addable(false)->deletable(false)->reorderable(false),
                ])
                ->action(function (PedidoEspecial $record, array $data) {
                    $falt = Materiales::faltantesPedido($record);
                    if ($falt || Materiales::tieneSolicitudAbierta($record)) {
                        if ($falt) Materiales::alarmaFaltante($record, $falt, 'intento de marcar listo');
                        \Filament\Notifications\Notification::make()->danger()->title('No se puede marcar listo')->body('Falta material o hay solicitudes pendientes. Se avisó a Operaciones y Dueño.')->send();
                        return;
                    }
                    // guardar los bultos confirmados por el taller
                    foreach ($data['bultos_items'] ?? [] as $bi) {
                        if (! empty($bi['item_id'])) {
                            \Illuminate\Support\Facades\DB::table('pedido_items')->where('id', $bi['item_id'])->update(['bultos' => max(1, (int) ($bi['bultos'] ?? 1))]);
                        }
                    }
                    EstadoPedidoErp::avanzar($record, 'listo_despacho');
                    Traza::registrar($record, 'listo_produccion', 'Producción marcó listo para despacho');
                    // avisar operaciones/despacho
                    $dest = DB::table('users')->whereIn('rol', ['operaciones', 'bodega', 'admin'])->where('activo', true)->pluck('email')->filter()->unique()->all();
                    $cuerpo = '<p>El pedido <strong>' . ($record->folio ?: $record->id) . '</strong> está <strong>listo para despacho</strong> (producción interna).</p>';
                    $html = CorreoBrand::wrap('Listo para despacho', $cuerpo);
                    foreach ($dest as $to) { try { Mail::to($to)->send(new DocumentoPedido('Listo para despacho · ' . ($record->folio ?: $record->id), $html, [])); } catch (\Throwable $e) { report($e); } }
                    // generar y descargar las etiquetas automáticamente
                    $pdfEtq = \App\Services\Etiquetas::generar($record->fresh());
                    \Filament\Notifications\Notification::make()->success()->title('Listo para despacho')->body('Etiquetas generadas. Descargando para imprimir...')->send();
                    return response()->download($pdfEtq, 'etiquetas-' . ($record->folio ?: $record->id) . '.pdf');
                }),
        ])
        ->defaultSort('fecha_comprometida');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListProduccion::route('/')];
    }
}
