<?php

namespace App\Filament\Pages;
use Filament\Schemas\Schema;

use App\Models\Cliente;
use App\Models\PedidoEspecial;
use App\Models\PedidoItemErp;
use App\Models\Producto;
use App\Services\Folios;
use App\Services\Traza;
use App\Support\Acl;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;

class CrearVentaBletia extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel = 'Nueva venta';
    protected static ?string $title = 'Nueva venta';
    protected static string|\UnitEnum|null $navigationGroup = 'Ventas';
    protected static ?int $navigationSort = -5;
    protected static bool $shouldRegisterNavigation = false;
    protected string $view = 'filament.pages.crear-venta-bletia';
    protected static ?string $slug = 'nueva-venta';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return in_array(Acl::rol(), ['admin', 'operaciones', 'contabilidad', 'vendedor'], true);
    }
    public static function shouldRegisterNavigation(): bool { return false; }

    public function mount(): void
    {
        $modo = in_array(request('modo'), ['stock', 'pedido'], true) ? request('modo') : 'pedido';
        $this->form->fill(['modo' => $modo, 'items' => [['tipo_item' => 'catalogo', 'cantidad' => 1]]]);
    }

    public function getTitle(): string
    {
        return request('modo') === 'stock' ? 'Vender stock' : (request('modo') === 'pedido' ? 'Vender bajo pedido' : 'Nueva venta');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Tipo de venta')->schema([
                Forms\Components\Select::make('modo')->label('¿Qué vas a vender?')
                    ->options(['stock' => 'Stock disponible', 'pedido' => 'Pedido (fabricación)'])
                    ->default('pedido')->required()->native(false)->live()->dehydrated()
                    ->disabled(fn () => in_array(request('modo'), ['stock', 'pedido'], true))
                    ->helperText('Stock: descuenta bodega y queda listo para despacho (venta directa). Pedido: pasa a aprobación y fabricación.'),
            ]),

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
                Forms\Components\DatePicker::make('fecha_solicitada')->label('Fecha solicitada')->minDate(now()),
                Forms\Components\Toggle::make('retira_local')->label('Retira en local')->default(false)->live(),
                Forms\Components\TextInput::make('direccion_envio')->label('Dirección de envío')->columnSpanFull()
                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => ! $get('retira_local')),
                Forms\Components\TextInput::make('ciudad_envio')->label('Ciudad de envío')
                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => ! $get('retira_local')),
                Forms\Components\TextInput::make('contacto_envio')->label('Contacto / teléfono')
                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => ! $get('retira_local')),
            ]),

            Forms\Components\Repeater::make('items')->label('Ítems')
                ->minItems(1)->defaultItems(1)->addActionLabel('Agregar ítem')->columns(2)
                ->schema([
                    Forms\Components\Select::make('tipo_item')->label('Tipo')
                        ->options(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('../../modo') === 'stock'
                            ? ['catalogo' => 'Catálogo']
                            : ['catalogo' => 'Catálogo', 'especial' => 'Otro diseño'])
                        ->default('catalogo')->required()->live()
                        ->dehydrateStateUsing(fn ($state) => $state ?: 'catalogo'),

                    Forms\Components\Select::make('producto_id')->label('Producto del catálogo')
                        ->options(fn () => Producto::where('activo', true)->orderBy('nombre')->pluck('nombre', 'id'))
                        ->searchable()->live()
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tipo_item') === 'catalogo')
                        ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tipo_item') === 'catalogo')
                        ->afterStateUpdated(function ($state, \Filament\Schemas\Components\Utilities\Set $set) {
                            $set('variante_id', null);
                            if ($state && ($p = Producto::find($state))) { $set('precio', (float) $p->precio); $set('nombre', $p->nombre); }
                        })
                        ->columnSpanFull(),

                    Forms\Components\Hidden::make('variante_id'),
                    Forms\Components\Select::make('atr_1')->label('Tapiz')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tipo_item') === 'catalogo' && self::prodTieneAttr($get('producto_id'), 1))
                        ->options(fn (\Filament\Schemas\Components\Utilities\Get $get) => self::opcionesProducto($get('producto_id'), 1))
                        ->live()->afterStateUpdated(fn (\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set) => self::resolverVariante($get, $set)),
                    Forms\Components\Select::make('atr_2')->label('Lado')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tipo_item') === 'catalogo' && self::prodTieneAttr($get('producto_id'), 2))
                        ->options(fn (\Filament\Schemas\Components\Utilities\Get $get) => self::opcionesProducto($get('producto_id'), 2))
                        ->live()->afterStateUpdated(fn (\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set) => self::resolverVariante($get, $set)),
                    Forms\Components\Select::make('atr_3')->label('Madera')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tipo_item') === 'catalogo' && self::prodTieneAttr($get('producto_id'), 3))
                        ->options(fn (\Filament\Schemas\Components\Utilities\Get $get) => self::opcionesProducto($get('producto_id'), 3))
                        ->live()->afterStateUpdated(fn (\Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set) => self::resolverVariante($get, $set)),

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
                        ->options(['' => 'Sin ajuste', 'pct' => 'Porcentaje (%)', 'monto' => 'Valor ($)'])
                        ->default('')->live()
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
                    Forms\Components\FileUpload::make('foto_adicional')->label('Foto (motivo)')->image()->directory('pedido-local')->disk('public')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tipo_item') === 'catalogo' && in_array($get('ajuste_modo'), ['pct', 'monto'], true)),

                    Forms\Components\TextInput::make('tapiz_principal')->label('Tapiz principal')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('../../modo') === 'pedido' && $get('tipo_item') === 'especial'),
                    Forms\Components\FileUpload::make('foto_tapiz_principal')->label('Foto tapiz principal')->image()->directory('pedido-local')->disk('public')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('../../modo') === 'pedido' && $get('tipo_item') === 'especial'),
                    Forms\Components\TextInput::make('cojines')->label('Cojines')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('../../modo') === 'pedido' && $get('tipo_item') === 'especial'),
                    Forms\Components\FileUpload::make('foto_cojines')->label('Foto cojines')->image()->directory('pedido-local')->disk('public')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('../../modo') === 'pedido' && $get('tipo_item') === 'especial'),
                    Forms\Components\TextInput::make('lacado')->label('Lacado')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('../../modo') === 'pedido' && $get('tipo_item') === 'especial'),
                    Forms\Components\FileUpload::make('foto_lacado')->label('Foto lacado')->image()->directory('pedido-local')->disk('public')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('../../modo') === 'pedido' && $get('tipo_item') === 'especial'),
                    Forms\Components\Textarea::make('notas_adicionales')->label('Notas')->rows(2)->columnSpanFull()
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('../../modo') === 'pedido'),
                ]),
        ])->statePath('data');
    }


    /** Resumen en vivo (PVP con IVA incluido al 15%). */
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
            if ($esCatalogo && ! empty($it['producto_id']) && ($p = \App\Models\Producto::find($it['producto_id']))) $rate = (float) $p->iva_rate;
            $neto = $rate > 0 ? round($sub / (1 + $rate / 100), 2) : $sub;
            $nombre = '—';
            if ($esCatalogo && ! empty($it['producto_id']) && ($p = \App\Models\Producto::find($it['producto_id']))) $nombre = $p->nombre;
            elseif (! empty($it['nombre'])) $nombre = $it['nombre'];
            $ajusteTxt = $modo === 'pct' ? (($signo>0?'+':'−').$pct.'%') : ($modo === 'monto' ? (($signo>0?'+$':'−$').number_format($monto,2)) : '—');
            $filas[] = ['nombre' => $nombre, 'cant' => $cant, 'pvp' => $pvp, 'ajuste' => $ajusteTxt, 'unit' => $unit, 'sub' => $sub];
            $base += $neto; $iva += ($sub - $neto); $total += $sub;
        }
        return ['filas' => $filas, 'base' => round($base, 2), 'iva' => round($iva, 2), 'total' => round($total, 2)];
    }


    /** ¿el producto tiene variantes que usan el atributo $aid? */
    protected static function prodTieneAttr($pid, int $aid): bool
    {
        if (! $pid) return false;
        foreach (\App\Models\Variante::where('producto_id', $pid)->get() as $v) {
            $o = (array) ($v->opciones ?: []);
            if (array_key_exists($aid, $o) && filled($o[$aid])) return true;
        }
        return false;
    }

    /** opciones (id=>valor) del atributo $aid usadas por las variantes del producto */
    protected static function opcionesProducto($pid, int $aid): array
    {
        if (! $pid) return [];
        $usadas = [];
        foreach (\App\Models\Variante::where('producto_id', $pid)->get() as $v) {
            $o = (array) ($v->opciones ?: []);
            if (isset($o[$aid]) && filled($o[$aid])) $usadas[(int) $o[$aid]] = true;
        }
        if (! $usadas) return [];
        return \App\Models\AtributoOpcion::whereIn('id', array_keys($usadas))->pluck('valor', 'id')->all();
    }

    /** resuelve la variante exacta según atr_1/atr_2/atr_3 y fija precio/foto/variante_id */
    protected static function resolverVariante($get, $set): void
    {
        $pid = $get('producto_id');
        if (! $pid) return;
        $sel = [];
        foreach ([1, 2, 3] as $aid) { $val = $get('atr_' . $aid); if (filled($val)) $sel[$aid] = (int) $val; }
        if (! $sel) return;
        foreach (\App\Models\Variante::where('producto_id', $pid)->get() as $v) {
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

    protected static function prodTiene($get, string $attr): bool
    {
        $pid = $get('producto_id');
        if (! $pid) return false;
        return \Illuminate\Support\Facades\DB::table('variantes')->where('producto_id', $pid)
            ->where('nombre', 'like', '%' . $attr . '%')->exists();
    }

    public function crear()
    {
        $data = $this->form->getState();
        $esStock = ($data['modo'] ?? 'pedido') === 'stock';
        $cli = ! empty($data['cliente_id']) ? Cliente::find($data['cliente_id']) : null;
        $items = $data['items'] ?? [];
        if (! $items) { Notification::make()->danger()->title('Agrega al menos un ítem')->send(); return; }

        $totalPedido = 0.0; $netoPedido = 0.0; $ivaPedido = 0.0;

        $pedido = PedidoEspecial::create([
            'codigo'     => 'BS-' . now()->format('ymd') . '-' . strtoupper(Str::random(4)),
            'folio'      => Folios::next('PED'),
            'origen'     => 'local',
            'cliente_id' => $data['cliente_id'] ?? null,
            'email'      => $cli->email ?? null,
            'estado'     => 'pendiente_pago',
            'estado_erp' => 'pendiente',
            'tipo_erp'   => 'local',
            'vendedor_id' => auth()->id(),
            'local_id'    => auth()->user()->local_id ?? null,
            'forma_venta' => $esStock ? 'stock' : 'local',
            'fecha_solicitada' => $data['fecha_solicitada'] ?? null,
            'retira_local' => (bool) ($data['retira_local'] ?? false),
            'direccion_envio' => $data['direccion_envio'] ?? null,
            'ciudad_envio' => $data['ciudad_envio'] ?? null,
            'contacto_envio' => $data['contacto_envio'] ?? null,
            'subtotal'   => 0, 'iva' => 0, 'total' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        foreach ($items as $it) {
            $cant = max(1, (int) ($it['cantidad'] ?? 1));
            $esCatalogo = ($it['tipo_item'] ?? 'catalogo') === 'catalogo';
            $pvpBase = (float) ($it['precio'] ?? 0);
            $adModo = $esCatalogo ? ($it['ajuste_modo'] ?? '') : '';
            $signo = ($it['ajuste_signo'] ?? 'menos') === 'mas' ? 1 : -1;
            $pct = $adModo === 'pct' ? (float) ($it['descuento_pct'] ?? 0) : 0;
            $monto = $adModo === 'monto' ? (float) ($it['valor_adicional'] ?? 0) : 0;
            $precio = $adModo === 'pct'
                ? round($pvpBase + $signo * ($pvpBase * $pct / 100), 2)
                : round($pvpBase + $signo * $monto, 2);
            $precio = max(0, $precio);
            $descPct = $signo * $pct; $adic = $signo * $monto;
            $prod = $esCatalogo && ! empty($it['producto_id']) ? Producto::find($it['producto_id']) : null;
            $rate = $prod ? (float) $prod->iva_rate : 15;
            $sub = round($precio * $cant, 2);
            $neto = $rate > 0 ? round($sub / (1 + $rate / 100), 2) : $sub;
            $iva = round($sub - $neto, 2);
            $totalPedido += $sub; $netoPedido += $neto; $ivaPedido += $iva;

            $fotoModelo = null; $fotosRef = []; $varLabel = null;
            if ($esCatalogo && $prod) {
                $fotoModelo = $prod->imagen_principal ?? null;
                if (! empty($it['variante_id']) && ($vv = \App\Models\Variante::find($it['variante_id']))) {
                    if ($vv->foto) $fotoModelo = $vv->foto;
                    $varLabel = $vv->combo_label ?: ($vv->nombre ?: null);
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
                'descuento_pct'    => $descPct ?: null,
                'valor_adicional'  => $adic ?: null,
                'motivo_adicional' => ($adModo ? ($it['motivo_adicional'] ?? null) : null),
                'foto_adicional'   => ($adModo ? ($it['foto_adicional'] ?? null) : null),
                'tapiz_principal'    => $it['tapiz_principal'] ?? null,
                'cojines'            => $it['cojines'] ?? null,
                'lacado'             => $it['lacado'] ?? null,
                'notas_adicionales'  => $it['notas_adicionales'] ?? null,
                'foto_tapiz_principal' => $it['foto_tapiz_principal'] ?? null,
                'foto_cojines'         => $it['foto_cojines'] ?? null,
                'foto_lacado'          => $it['foto_lacado'] ?? null,
                'foto_modelo'        => $fotoModelo,
                'fotos_ref'          => $fotosRef ?: null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        \Illuminate\Support\Facades\DB::table('pedidos')->where('id', $pedido->id)->update([
            'subtotal' => round($netoPedido, 2), 'iva' => round($ivaPedido, 2), 'total' => round($totalPedido, 2),
        ]);

        if ($cli && ! ($data['retira_local'] ?? false)) {
            $updC = array_filter(['direccion' => $data['direccion_envio'] ?? null, 'ciudad' => $data['ciudad_envio'] ?? null]);
            if ($updC) \Illuminate\Support\Facades\DB::table('clientes')->where('id', $cli->id)->update($updC);
        }

        Traza::registrar($pedido, 'vendido');

        if ($esStock) {
            foreach ($items as $it) {
                if (($it['tipo_item'] ?? '') === 'catalogo' && ! empty($it['producto_id'])) {
                    \App\Models\MovimientoStock::create([
                        'producto_id' => $it['producto_id'],
                        'local_id' => auth()->user()->local_id ?? \App\Models\Local::value('id'),
                        'tipo' => 'salida', 'cantidad' => max(1, (int) ($it['cantidad'] ?? 1)),
                        'referencia' => $pedido->folio, 'nota' => 'Venta de stock',
                    ]);
                }
            }
            \App\Services\EstadoPedidoErp::avanzar($pedido, 'en_bodega', false);
            Notification::make()->success()->title('Venta de stock registrada')->body('Folio ' . $pedido->folio . '. Stock descontado, listo para despacho.')->send();
        } else {
            \App\Services\EstadoPedidoErp::avanzar($pedido, 'por_aprobar', false);
            Traza::registrar($pedido, 'enviado_aprobacion');
            \App\Services\FlujoErp::alarmaPorAprobar($pedido->fresh());
            Notification::make()->success()->title('Pedido creado')->body('Folio ' . $pedido->folio . ' enviado a aprobación.')->send();
        }

        return redirect(\App\Filament\Resources\PedidoEspecialResource::getUrl());
    }
}
