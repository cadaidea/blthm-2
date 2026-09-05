<?php

namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;



use App\Forms\Components\EditorJsField;
use App\Support\Acl;
use App\Filament\Resources\ProductoResource\Pages;
use App\Models\Producto;
use Filament\Forms;
use Filament\Schemas\Components\Utilities\Set;
use App\Models\Atributo;
use App\Models\AtributoOpcion;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductoResource extends Resource
{
    protected static ?string $model = Producto::class;


    public static function canViewAny(): bool
    {
        return Acl::ve(static::class);
    }

    public static function canCreate(): bool
    {
        return ! Acl::esVendedor() && ! Acl::esContabilidad();
    }

    public static function canEdit($record): bool
    {
        return ! Acl::esVendedor() && ! Acl::esContabilidad();
    }

    public static function canDelete($record): bool
    {
        return Acl::puedeEliminar();
    }
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return \App\Models\Producto::query()->with(['categoria']);
    }
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';
    protected static string|\UnitEnum|null $navigationGroup = 'Catálogo';
    protected static ?string $modelLabel = 'Producto';
    protected static ?string $pluralModelLabel = 'Productos';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Tabs::make('Producto')->columnSpanFull()->tabs([

                \Filament\Schemas\Components\Tabs\Tab::make('General')->schema([
                    \Filament\Schemas\Components\Section::make('Información')->columns(2)->columnSpanFull()->schema([
                        Forms\Components\TextInput::make('nombre')->required()->maxLength(191)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, \Filament\Schemas\Components\Utilities\Set $set) => $set('slug', \Illuminate\Support\Str::slug((string) $state))),
                        Forms\Components\TextInput::make('slug')->maxLength(191)->helperText('Se genera solo.'),
                        Forms\Components\Select::make('categoria_id')->label('Categoría')
                            ->relationship('categoria', 'nombre')->searchable()->preload(),
                        Forms\Components\TextInput::make('sku')->label('SKU')->maxLength(64),
                        Forms\Components\Textarea::make('descripcion_corta')->label('Descripción corta')->rows(2)->columnSpanFull(),
                        EditorJsField::make('descripcion_json')->label('Descripción')->columnSpanFull(),
                    ]),
                    \Filament\Schemas\Components\Section::make('Origen')->columns(2)->columnSpanFull()->schema([
                        Forms\Components\Select::make('origen')
                            ->label('Origen del producto')
                            ->options(['propio' => 'Propio', 'proveedor' => 'De proveedor'])
                            ->default('propio')->live(),
                        Forms\Components\Select::make('proveedor_default_id')
                            ->label('Proveedor')
                            ->options(fn () => \App\Models\Proveedor::where('activo', true)->pluck('nombre', 'id'))
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('origen') === 'proveedor')
                            ->searchable(),
                    ]),
                ]),

                \Filament\Schemas\Components\Tabs\Tab::make('Precio e inventario')->schema([
                    \Filament\Schemas\Components\Section::make('Precio e inventario')->columns(3)->columnSpanFull()->schema([
                        Forms\Components\TextInput::make('precio')->label('PVP (IVA incluido)')->numeric()->prefix('$')->required()->default(0)
                            ->helperText('Ingresa el precio final de venta. El IVA se calcula solo.'),
                        Forms\Components\TextInput::make('costo_produccion')->label('Costo producción (taller)')->numeric()->prefix('$')
                            ->visible(fn () => \App\Support\Acl::esAdmin() || \App\Support\Acl::esContabilidad())
                            ->helperText('Costo interno cuando lo fabrica el taller propio.'),
                        Forms\Components\TextInput::make('costo_proveedor')->label('Costo proveedor')->numeric()->prefix('$')
                            ->visible(fn () => \App\Support\Acl::esAdmin() || \App\Support\Acl::esContabilidad())
                            ->helperText('Costo interno cuando lo fabrica un proveedor externo.'),
                        Forms\Components\Toggle::make('grava_iva')->label('Producto gravado con IVA')
                            ->default(true)
                            ->helperText('Muebles: normalmente sí. El porcentaje sale del IVA vigente; el precio se ingresa CON IVA incluido.')
                            ->dehydrated(false)
                            ->afterStateHydrated(fn ($component, $record) => $component->state(! $record || (float) ($record->iva_rate ?? 15) > 0)),
                        Forms\Components\Hidden::make('iva_rate')
                            ->default(fn () => \App\Support\Impuestos::ivaVigente())
                            ->dehydrateStateUsing(fn ($state, $get) => $get('grava_iva') ? \App\Support\Impuestos::ivaVigente() : 0),
                        Forms\Components\TextInput::make('bultos_default')->label('Bultos estándar')->numeric()->minValue(1)->default(1)
                            ->helperText('Cuántos paquetes/bultos ocupa normalmente este producto al despacharse.'),
                        Forms\Components\TextInput::make('dias_fabricacion')->label('Días de fabricación')->numeric()->minValue(0)
                            ->helperText('Cuántos días tarda el taller en fabricar este mueble. Se usa para planificar la cola de producción.'),
                        \Filament\Schemas\Components\Group::make([
                            Forms\Components\Toggle::make('activo')->default(true),
                            Forms\Components\Toggle::make('destacado'),
                            Forms\Components\Toggle::make('permitir_pedido')->label('Permitir compra bajo pedido (Made to Order, sin stock)'),
                            Forms\Components\TextInput::make('mto_texto')->label('Texto Made to Order')->placeholder('Made to Order · entrega estimada 3–4 semanas')->columnSpanFull(),
                        ]),
                        Forms\Components\Repeater::make('stock')->relationship()->label('Stock general (solo si el producto NO tiene combinaciones)')
                            ->columns(3)->columnSpanFull()->schema([
                                Forms\Components\Select::make('local_id')->label('Ubicación')->relationship('local', 'nombre')->required(),
                                Forms\Components\TextInput::make('cantidad')->numeric()->default(0),
                                Forms\Components\TextInput::make('minimo')->label('Mínimo')->numeric()->default(0),
                            ])->defaultItems(0)
                            ->helperText('Si el producto tiene combinaciones (Tapiz/Lado/Madera), carga el stock dentro de cada combinación más abajo, no aquí.'),
                    ]),
                ]),

                \Filament\Schemas\Components\Tabs\Tab::make('Imágenes y variantes')->schema([
                    \Filament\Schemas\Components\Section::make('Imágenes')->columnSpanFull()->schema([
                        Forms\Components\Repeater::make('imagenes')->relationship()->label('')
                            ->columns(2)->orderColumn('orden')->schema([
                                Forms\Components\FileUpload::make('ruta')->label('Imagen')->image()
                                    ->directory('productos')->disk('public')->imageEditor()->required()
                                    ->saveUploadedFileUsing(\App\Support\WebpUpload::handler())
                                    ->imagePreviewHeight('120'),
                                Forms\Components\TextInput::make('alt')->label('Texto alternativo (SEO)'),
                            ])->defaultItems(0),
                    ]),

                    \Filament\Schemas\Components\Section::make('Variantes (combinaciones del producto)')
                        ->description('Cada fila es una combinación a la venta. Lado/Tapiz/Madera son opcionales; PVP, costo y foto son obligatorios. Agrega una fila por cada combinación.')
                        ->schema([
                        Forms\Components\Repeater::make('variantes')->relationship()->label('')
                            ->columns(2)->schema([
                                \Filament\Schemas\Components\Fieldset::make('Opciones (todas opcionales)')->columns(3)->schema(
                                    Atributo::with('opciones')->orderBy('orden')->get()->isEmpty()
                                        ? [Forms\Components\Placeholder::make('sin_vars')->label('')->content('Crea variables en Catálogo → Variables de producto.')]
                                        : Atributo::with('opciones')->orderBy('orden')->get()->map(function ($a) {
                                            return Forms\Components\Select::make('opciones.' . $a->id)
                                                ->label($a->nombre)
                                                ->options($a->opciones->pluck('valor', 'id'))
                                                ->placeholder('—')->native(false);
                                        })->all()
                                ),
                                Forms\Components\TextInput::make('pvp')->label('PVP (IVA incl.)')->numeric()->prefix('$')
                                    ->helperText('Vacío = usa el precio general del producto.'),
                                Forms\Components\TextInput::make('costo')->label('Costo compra/fabricación')->numeric()->prefix('$')
                                    ->helperText('Vacío = usa el costo general del producto.'),
                                Forms\Components\FileUpload::make('foto')->label('Foto de esta combinación')
                                    ->directory('variantes')->disk('public')
                                    ->image()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(4096)
                                    ->saveUploadedFileUsing(\App\Support\WebpUpload::handler())
                                    ->required(fn (string $operation): bool => $operation === 'create'),
                                Forms\Components\Repeater::make('stock')->relationship()->label('Stock por ubicación (de esta combinación)')
                                    ->columnSpanFull()->columns(3)->schema([
                                        Forms\Components\Select::make('local_id')->label('Ubicación')->relationship('local', 'nombre')->required(),
                                        Forms\Components\TextInput::make('cantidad')->numeric()->default(0),
                                        Forms\Components\TextInput::make('minimo')->label('Mínimo')->numeric()->default(0),
                                    ])->defaultItems(0)->addActionLabel('Agregar ubicación'),
                            ])->defaultItems(0)->addActionLabel('Agregar combinación')
                            ->itemLabel(function (array $state) {
                                $ids = collect($state['opciones'] ?? [])->filter()->values();
                                if ($ids->isEmpty()) return $state['pvp'] ?? null;
                                $vals = \App\Models\AtributoOpcion::whereIn('id', $ids)->pluck('valor')->implode(' · ');
                                return $vals . ($state['pvp'] ?? null ? '  —  $' . $state['pvp'] : '');
                            }),
                    ])->collapsed(),
                ]),

                \Filament\Schemas\Components\Tabs\Tab::make('SEO')->schema([
                    \Filament\Schemas\Components\Section::make('SEO')->columns(1)->columnSpanFull()->schema([
                        Forms\Components\TextInput::make('meta_title')->label('Meta título')->maxLength(70),
                        Forms\Components\Textarea::make('meta_description')->label('Meta descripción')->rows(2)->maxLength(320),
                    ]),
                ]),

            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->searchable()->sortable()->limit(40),
                Tables\Columns\TextColumn::make('categoria.nombre')->label('Categoría')->sortable(),
                Tables\Columns\TextColumn::make('precio')->label('PVP')->money('USD')->sortable(),
                Tables\Columns\TextColumn::make('stock_total')->label('Stock')->state(fn (Producto $r) => $r->stock_total),
                Tables\Columns\IconColumn::make('activo')->boolean(),
                Tables\Columns\IconColumn::make('permitir_pedido')->label('Bajo pedido')->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('activo'),
                Tables\Filters\SelectFilter::make('categoria_id')->relationship('categoria', 'nombre')->label('Categoría'),
            ])
            ->actions([Actions\EditAction::make()->visible(fn () => ! Acl::esVendedor() && ! Acl::esContabilidad())])
            ->bulkActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProductos::route('/'),
            'create' => Pages\CreateProducto::route('/create'),
            'edit'   => Pages\EditProducto::route('/{record}/edit'),
        ];
    }
}