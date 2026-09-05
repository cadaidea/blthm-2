<?php

namespace App\Filament\Pages;
use Filament\Schemas\Schema;

use App\Filament\Resources\PedidoEspecialResource;
use App\Models\Atributo;
use App\Models\AtributoOpcion;
use App\Models\Cliente;
use App\Models\PedidoEspecial;
use App\Models\PedidoItemErp;
use App\Models\Producto;
use App\Models\Variante;
use App\Services\Folios;
use App\Services\Traza;
use App\Support\Acl;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CrearPedidoBletia extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-plus';
    protected static ?string $title = 'Nuevo pedido';
    protected string $view = 'filament.pages.crear-pedido-bletia';
    protected static ?string $slug = 'pedido-nuevo';
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return in_array(Acl::rol(), ['admin', 'operaciones', 'contabilidad', 'vendedor'], true);
    }
    public static function shouldRegisterNavigation(): bool { return false; }

    public function mount(): void
    {
        $this->form->fill(['items' => [['tipo_item' => 'catalogo', 'cantidad' => 1]]]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Cliente y entrega')->columns(2)->schema([
                Forms\Components\Select::make('cliente_id')->label('Cliente')
                    ->options(fn () => Cliente::orderBy('nombre')->pluck('nombre', 'id'))->searchable()
                    ->helperText('Elige existente o crea uno nuevo.')
                    ->createOptionForm([
                        Forms\Components\TextInput::make('nombre')->required(),
                        Forms\Components\TextInput::make('identificacion')->label('Cédula / RUC'),
                        Forms\Components\TextInput::make('email')->email(),
                        Forms\Components\TextInput::make('celular')->label('Celular')->tel(),
                        Forms\Components\TextInput::make('direccion')->label('Dirección'),
                        Forms\Components\TextInput::make('ciudad'),
                    ])
                    ->createOptionUsing(fn (array $data) => Cliente::create($data)->id),
                Forms\Components\DatePicker::make('fecha_solicitada')->label('Fecha de entrega')->minDate(now())->required(),
                Forms\Components\Toggle::make('retira_local')->label('Retira en local')->default(false)->live()
                    ->helperText('Si está activo, no se piden datos de envío.'),
                Forms\Components\TextInput::make('nombre_recibe')->label('Nombre de quien recibe')
                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => ! $get('retira_local')),
                Forms\Components\TextInput::make('contacto_envio')->label('Teléfono de contacto')
                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => ! $get('retira_local')),
                Forms\Components\TextInput::make('direccion_envio')->label('Dirección de entrega')->columnSpanFull()
                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => ! $get('retira_local')),
                Forms\Components\TextInput::make('ciudad_envio')->label('Ciudad')
                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => ! $get('retira_local')),
                Forms\Components\TextInput::make('horario_entrega')->label('Horario (opcional)')
                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => ! $get('retira_local')),
            ]),

            Forms\Components\Repeater::make('items')->label('Ítems del pedido')
                ->minItems(1)->defaultItems(1)->addActionLabel('Agregar ítem')->columns(2)
                ->schema([
                    Forms\Components\Select::make('tipo_item')->label('Tipo')
                        ->options(['catalogo' => 'Catálogo', 'especial' => 'Otro diseño'])
                        ->default('catalogo')->required()->live(),

                    Forms\Components\Select::make('producto_id')->label('Producto del catálogo')
                        ->options(fn () => Producto::where('activo', true)->orderBy('nombre')->pluck('nombre', 'id'))
                        ->searchable()->live()
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tipo_item') === 'catalogo')
                        ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tipo_item') === 'catalogo')
                        ->afterStateUpdated(function ($state, \Filament\Schemas\Components\Utilities\Set $set) {
                            $set('atr_1', null); $set('atr_2', null); $set('atr_3', null);
                            $set('variante_id', null); $set('foto_preview', null);
                            if ($state && ($p = Producto::find($state))) {
                                $set('precio', (float) $p->precio);
                                $set('nombre', $p->nombre);
                                self::autocompletarUnica($state, $set);
                            }
                        })
                        ->columnSpanFull(),

                    // Atributos del producto (solo los que tenga). Tapiz=1, Lado=2, Madera=3
                    Forms\Components\Select::make('atr_1')->label('Tapiz')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tipo_item') === 'catalogo' && self::prodTieneAttr($get('producto_id'), 1))
                        ->options(fn (\Filament\Schemas\Components\Utilities\Get $get) => self::opcionesProducto($get('producto_id'), 1))
                        ->live()->afterStateUpdated(fn (\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set) => self::resolver($get, $set)),
                    Forms\Components\Select::make('atr_2')->label('Lado')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tipo_item') === 'catalogo' && self::prodTieneAttr($get('producto_id'), 2))
                        ->options(fn (\Filament\Schemas\Components\Utilities\Get $get) => self::opcionesProducto($get('producto_id'), 2))
                        ->live()->afterStateUpdated(fn (\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set) => self::resolver($get, $set)),
                    Forms\Components\Select::make('atr_3')->label('Madera')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tipo_item') === 'catalogo' && self::prodTieneAttr($get('producto_id'), 3))
                        ->options(fn (\Filament\Schemas\Components\Utilities\Get $get) => self::opcionesProducto($get('producto_id'), 3))
                        ->live()->afterStateUpdated(fn (\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set) => self::resolver($get, $set)),

                    Forms\Components\Placeholder::make('preview')->label('Foto del modelo')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => filled($get('foto_preview')))
                        ->content(fn (\Filament\Schemas\Components\Utilities\Get $get) => filled($get('foto_preview'))
                            ? new \Illuminate\Support\HtmlString('<img src="' . e($get('foto_preview')) . '" style="max-height:150px;border-radius:10px;border:1px solid #eee" />')
                            : '—')
                        ->columnSpanFull(),
                    Forms\Components\Hidden::make('foto_preview'),
                    Forms\Components\Hidden::make('variante_id'),

                    // Otro diseño
                    Forms\Components\TextInput::make('nombre')->label('Nombre del diseño')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tipo_item') === 'especial')
                        ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tipo_item') === 'especial'),
                    Forms\Components\FileUpload::make('fotos_especial')->label('Fotos del diseño (1 a 3)')
                        ->image()->multiple()->minFiles(1)->maxFiles(3)->directory('pedido-local')->disk('public')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tipo_item') === 'especial')
                        ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tipo_item') === 'especial')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('cantidad')->numeric()->default(1)->minValue(1)->required()->live(onBlur: true),
                    Forms\Components\TextInput::make('precio')->label('PVP (IVA incl.)')->numeric()->prefix('$')->default(0)
                        ->disabled(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tipo_item') === 'catalogo')->dehydrated()
                        ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tipo_item') === 'especial')
                        ->helperText(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tipo_item') === 'catalogo' ? 'Sale del producto/variante.' : 'IVA incluido.'),

                    Forms\Components\Select::make('ajuste_modo')->label('Ajuste de precio')
                        ->options(['' => 'Sin ajuste', 'pct' => 'Porcentaje (%)', 'monto' => 'Valor ($)'])->default('')->live()
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tipo_item') === 'catalogo'),
                    Forms\Components\Select::make('ajuste_signo')->label('Sentido')
                        ->options(['mas' => 'Aumentar (+)', 'menos' => 'Descontar (−)'])->default('menos')->live()
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tipo_item') === 'catalogo' && in_array($get('ajuste_modo'), ['pct', 'monto'], true)),
                    Forms\Components\TextInput::make('descuento_pct')->label('Porcentaje')->numeric()->suffix('%')->default(0)->minValue(0)->maxValue(100)->live(onBlur: true)
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tipo_item') === 'catalogo' && $get('ajuste_modo') === 'pct'),
                    Forms\Components\TextInput::make('valor_adicional')->label('Valor ($)')->numeric()->prefix('$')->default(0)->minValue(0)->live(onBlur: true)
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tipo_item') === 'catalogo' && $get('ajuste_modo') === 'monto'),
                    Forms\Components\TextInput::make('motivo_adicional')->label('Motivo del ajuste')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tipo_item') === 'catalogo' && in_array($get('ajuste_modo'), ['pct', 'monto'], true))
                        ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tipo_item') === 'catalogo' && in_array($get('ajuste_modo'), ['pct', 'monto'], true)),

                    // Detalles y acabados (opcionales)
                    \Filament\Schemas\Components\Fieldset::make('Detalles y acabados')->columns(2)->columnSpanFull()->schema([
                        Forms\Components\TextInput::make('tapiz_principal')->label('Tapiz principal'),
                        Forms\Components\FileUpload::make('foto_tapiz_principal')->label('Foto tapiz principal')->image()->directory('pedido-local')->disk('public'),
                        Forms\Components\TextInput::make('tapiz_secundario')->label('Tapiz secundario'),
                        Forms\Components\FileUpload::make('foto_tapiz_secundario')->label('Foto tapiz secundario')->image()->directory('pedido-local')->disk('public'),
                        Forms\Components\TextInput::make('cojines')->label('Cojines alternos'),
                        Forms\Components\FileUpload::make('foto_cojines')->label('Foto cojines')->image()->directory('pedido-local')->disk('public'),
                        Forms\Components\TextInput::make('lacado')->label('Lacado'),
                        Forms\Components\FileUpload::make('foto_lacado')->label('Foto lacado')->image()->directory('pedido-local')->disk('public'),
                        Forms\Components\Textarea::make('notas_adicionales')->label('Notas')->rows(2)->columnSpanFull(),
                    ]),
                ]),
        ])->statePath('data');
    }

    /* ---------- helpers de atributos/variante ---------- */

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
        return AtributoOpcion::whereIn('id', array_keys($usadas))->pluck('valor', 'id')->all();
    }

    /** Si un atributo tiene una sola opción para el producto, la fija sola y vuelca a su campo. */
    protected static function autocompletarUnica($pid, $set): void
    {
        foreach ([1, 2, 3] as $aid) {
            $ops = self::opcionesProducto($pid, $aid);
            if (count($ops) === 1) { $set('atr_' . $aid, (int) array_key_first($ops)); }
        }
        self::resolverPorPid($pid, $set);
    }

    protected static function resolver($get, $set): void
    {
        $pid = $get('producto_id');
        $sel = [];
        foreach ([1, 2, 3] as $aid) { $val = $get('atr_' . $aid); if (filled($val)) $sel[$aid] = (int) $val; }
        self::aplicar($pid, $sel, $set);
    }

    protected static function resolverPorPid($pid, $set): void
    {
        // tras autocompletar únicas, re-evalúa con lo que haya
        $sel = [];
        foreach ([1, 2, 3] as $aid) {
            $ops = self::opcionesProducto($pid, $aid);
            if (count($ops) === 1) $sel[$aid] = (int) array_key_first($ops);
        }
        self::aplicar($pid, $sel, $set);
    }

    /** Resuelve variante exacta, fija precio/foto y vuelca valores a los campos de specs. */
    protected static function aplicar($pid, array $sel, $set): void
    {
        if (! $pid) return;
        // volcar valores de texto a los campos de specs
        $map = [1 => 'tapiz_principal', 2 => 'lado', 3 => 'lacado'];
        foreach ($sel as $aid => $oid) {
            $valor = AtributoOpcion::where('id', $oid)->value('valor');
            if (isset($map[$aid]) && $valor) $set($map[$aid], $valor);
        }
        if (! $sel) return;
        foreach (Variante::where('producto_id', $pid)->get() as $v) {
            $op = [];
            foreach ((array) ($v->opciones ?: []) as $aid => $oid) { if (filled($oid)) $op[(int) $aid] = (int) $oid; }
            $coincide = true;
            foreach ($sel as $aid => $oid) { if (($op[$aid] ?? null) !== $oid) { $coincide = false; break; } }
            if ($coincide && count($op) === count($sel)) {
                $set('variante_id', $v->id);
                $set('precio', $v->pvp_final);
                if ($v->foto) $set('foto_preview', Storage::disk('public')->url($v->foto));
                return;
            }
        }
    }

    public function getResumen(): array
    {
        $items = $this->data['items'] ?? [];
        $filas = []; $base = 0.0; $iva = 0.0; $total = 0.0;
        foreach ($items as $it) {
            $esCatalogo = ($it['tipo_item'] ?? 'catalogo') === 'catalogo';
            $pvp = (float) ($it['precio'] ?? 0);
            $cant = max(1, (int) ($it['cantidad'] ?? 1));
            $modo = $esCatalogo ? ($it['ajuste_modo'] ?? '') : '';
            $signo = ($it['ajuste_signo'] ?? 'menos') === 'mas' ? 1 : -1;
            $pct = $modo === 'pct' ? (float) ($it['descuento_pct'] ?? 0) : 0;
            $monto = $modo === 'monto' ? (float) ($it['valor_adicional'] ?? 0) : 0;
            $unit = $modo === 'pct' ? round($pvp + $signo * ($pvp * $pct / 100), 2) : round($pvp + $signo * $monto, 2);
            $unit = max(0, $unit);
            $sub = round($unit * $cant, 2);
            $rate = 15.0;
            if ($esCatalogo && ! empty($it['producto_id']) && ($p = Producto::find($it['producto_id']))) $rate = (float) $p->iva_rate;
            $neto = $rate > 0 ? round($sub / (1 + $rate / 100), 2) : $sub;
            $nombre = $it['nombre'] ?? '—';
            if ($esCatalogo && ! empty($it['producto_id']) && ($p = Producto::find($it['producto_id']))) $nombre = $p->nombre;
            $ajuste = $modo === 'pct' ? (($signo > 0 ? '+' : '−') . $pct . '%') : ($modo === 'monto' ? (($signo > 0 ? '+$' : '−$') . number_format($monto, 2)) : '—');
            $filas[] = ['nombre' => $nombre, 'cant' => $cant, 'pvp' => $pvp, 'ajuste' => $ajuste, 'unit' => $unit, 'sub' => $sub];
            $base += $neto; $iva += ($sub - $neto); $total += $sub;
        }
        return ['filas' => $filas, 'base' => round($base, 2), 'iva' => round($iva, 2), 'total' => round($total, 2)];
    }

    public function crear()
    {
        $data = $this->form->getState();
        $cli = ! empty($data['cliente_id']) ? Cliente::find($data['cliente_id']) : null;
        $items = $data['items'] ?? [];
        if (! $items) { Notification::make()->danger()->title('Agrega al menos un ítem')->send(); return; }

        $totalPedido = 0.0; $netoPedido = 0.0; $ivaPedido = 0.0;

        $pedido = PedidoEspecial::create([
            'codigo'     => 'BS-' . now()->format('ymd') . '-' . strtoupper(Str::random(4)),
            'folio'      => Folios::next('PED'),
            'cliente_id' => $data['cliente_id'] ?? null,
            'email'      => $cli->email ?? null,
            'estado'     => 'pendiente_pago',
            'estado_erp' => 'pendiente',
            'tipo_erp'   => 'local',
            'vendedor_id' => auth()->id(),
            'local_id'    => auth()->user()->local_id ?? null,
            'forma_venta' => 'local',
            'fecha_solicitada' => $data['fecha_solicitada'] ?? null,
            'retira_local' => (bool) ($data['retira_local'] ?? false),
            'direccion_envio' => $data['direccion_envio'] ?? null,
            'ciudad_envio' => $data['ciudad_envio'] ?? null,
            'contacto_envio' => $data['contacto_envio'] ?? null,
            'nombre_recibe' => $data['nombre_recibe'] ?? null,
            'horario_entrega' => $data['horario_entrega'] ?? null,
            'subtotal'   => 0, 'iva' => 0, 'total' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        foreach ($items as $it) {
            $cant = max(1, (int) ($it['cantidad'] ?? 1));
            $esCatalogo = ($it['tipo_item'] ?? 'catalogo') === 'catalogo';
            $pvpBase = (float) ($it['precio'] ?? 0);
            $modo = $esCatalogo ? ($it['ajuste_modo'] ?? '') : '';
            $signo = ($it['ajuste_signo'] ?? 'menos') === 'mas' ? 1 : -1;
            $pct = $modo === 'pct' ? (float) ($it['descuento_pct'] ?? 0) : 0;
            $monto = $modo === 'monto' ? (float) ($it['valor_adicional'] ?? 0) : 0;
            $precio = $modo === 'pct' ? round($pvpBase + $signo * ($pvpBase * $pct / 100), 2) : round($pvpBase + $signo * $monto, 2);
            $precio = max(0, $precio);
            $prod = $esCatalogo && ! empty($it['producto_id']) ? Producto::find($it['producto_id']) : null;
            $rate = $prod ? (float) $prod->iva_rate : 15;
            $sub = round($precio * $cant, 2);
            $neto = $rate > 0 ? round($sub / (1 + $rate / 100), 2) : $sub;
            $iva = round($sub - $neto, 2);
            $totalPedido += $sub; $netoPedido += $neto; $ivaPedido += $iva;

            $fotoModelo = null; $fotosRef = []; $varLabel = null;
            if ($esCatalogo && $prod) {
                $fotoModelo = $prod->imagen_principal ?? null;
                if (! empty($it['variante_id']) && ($vv = Variante::find($it['variante_id']))) {
                    if ($vv->foto) $fotoModelo = $vv->foto;
                    $varLabel = $vv->combo_label ?: ($vv->nombre ? $vv->nombre . ': ' . $vv->valor : null);
                }
            } elseif (! empty($it['fotos_especial'])) {
                $fotosRef = array_values((array) $it['fotos_especial']);
                $fotoModelo = $fotosRef[0] ?? null;
            }

            PedidoItemErp::create([
                'pedido_id'   => $pedido->id,
                'producto_id' => $esCatalogo ? ($it['producto_id'] ?? null) : null,
                'nombre'      => $prod ? $prod->nombre : ($it['nombre'] ?? 'Diseño especial'),
                'variantes'   => $varLabel,
                'cantidad'    => $cant, 'precio' => $precio, 'iva_rate' => $rate, 'subtotal' => $sub,
                'pvp_base'         => $esCatalogo ? $pvpBase : null,
                'descuento_pct'    => $modo === 'pct' ? ($signo * $pct) : null,
                'valor_adicional'  => $modo === 'monto' ? ($signo * $monto) : null,
                'motivo_adicional' => $modo ? ($it['motivo_adicional'] ?? null) : null,
                'lado'               => $it['lado'] ?? null,
                'tapiz_principal'    => $it['tapiz_principal'] ?? null,
                'tapiz_secundario'   => $it['tapiz_secundario'] ?? null,
                'cojines'            => $it['cojines'] ?? null,
                'lacado'             => $it['lacado'] ?? null,
                'notas_adicionales'  => $it['notas_adicionales'] ?? null,
                'foto_tapiz_principal'  => $it['foto_tapiz_principal'] ?? null,
                'foto_tapiz_secundario' => $it['foto_tapiz_secundario'] ?? null,
                'foto_cojines'          => $it['foto_cojines'] ?? null,
                'foto_lacado'           => $it['foto_lacado'] ?? null,
                'foto_modelo'        => $fotoModelo,
                'fotos_ref'          => $fotosRef ?: null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        DB::table('pedidos')->where('id', $pedido->id)->update([
            'subtotal' => round($netoPedido, 2), 'iva' => round($ivaPedido, 2), 'total' => round($totalPedido, 2),
        ]);

        if ($cli && ! ($data['retira_local'] ?? false)) {
            $updC = array_filter(['direccion' => $data['direccion_envio'] ?? null, 'ciudad' => $data['ciudad_envio'] ?? null]);
            if ($updC) DB::table('clientes')->where('id', $cli->id)->update($updC);
        }

        Traza::registrar($pedido, 'vendido');
        \App\Services\EstadoPedidoErp::avanzar($pedido, 'por_aprobar', false);
        Traza::registrar($pedido, 'enviado_aprobacion');
        \App\Services\FlujoErp::alarmaPorAprobar($pedido->fresh());
        Notification::make()->success()->title('Pedido creado')->body('Folio ' . $pedido->folio . ' enviado a aprobación.')->send();

        return redirect(PedidoEspecialResource::getUrl('edit', ['record' => $pedido->getKey()]));
    }
}
