<?php

namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;

use App\Support\Acl;
use App\Filament\Resources\ReclamoResource\Pages;
use App\Models\Reclamo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class ReclamoResource extends Resource
{
    protected static ?string $model = Reclamo::class;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['cliente']);
    }
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-lifebuoy';
    protected static string|\UnitEnum|null $navigationGroup = 'Logística';
    protected static ?string $modelLabel = 'Reclamo / Garantía';
    protected static ?string $pluralModelLabel = 'Postventa (reclamos)';
    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        return Acl::ve(static::class);
    }

    public static function getNavigationBadge(): ?string
    {
        $n = Reclamo::whereNotIn('estado', ['resuelto', 'rechazado'])->count();
        return $n > 0 ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Origen del reclamo')->columns(2)->schema([
                Forms\Components\Select::make('pedido_id')->label('Pedido')->searchable()
                    ->options(fn () => DB::table('pedidos')->orderByDesc('id')->limit(200)->get()
                        ->mapWithKeys(fn ($p) => [$p->id => ($p->folio ?: ('#' . $p->id))])->all())
                    ->live()
                    ->afterStateUpdated(function ($state, \Filament\Schemas\Components\Utilities\Set $set) {
                        if (! $state) return;
                        $p = DB::table('pedidos')->where('id', $state)->first();
                        if ($p && $p->cliente_id) $set('cliente_id', $p->cliente_id);
                        $item = DB::table('pedido_items')->where('pedido_id', $state)->first();
                        if ($item) $set('producto', $item->nombre);
                    })
                    ->helperText('Selecciona el pedido entregado que origina el reclamo.'),
                Forms\Components\Select::make('cliente_id')->label('Cliente')->searchable()
                    ->options(fn () => DB::table('clientes')->orderBy('nombre')->get()
                        ->mapWithKeys(fn ($c) => [$c->id => $c->nombre])->all()),
                Forms\Components\TextInput::make('producto')->label('Producto / mueble'),
                Forms\Components\Select::make('tipo_problema')->label('Tipo de problema')
                    ->options([
                        'tapiz' => 'Tapiz (descosido, manchado, desgaste)',
                        'estructura' => 'Estructura / madera',
                        'esponja' => 'Esponja / relleno (hundido)',
                        'medida' => 'Medida incorrecta',
                        'acabado' => 'Acabado / lacado',
                        'transporte' => 'Daño en transporte',
                        'otro' => 'Otro',
                    ]),
            ]),
            \Filament\Schemas\Components\Section::make('Detalle')->schema([
                Forms\Components\TextInput::make('bultos')->label('Bultos (paquetes que regresan)')->numeric()->default(1)->minValue(1)
                    ->helperText('Cuántos paquetes/bultos regresarán al taller o proveedor. No siempre son todos los del pedido original.'),
                Forms\Components\Textarea::make('descripcion')->label('Descripción del problema')->rows(3)->columnSpanFull(),
                Forms\Components\FileUpload::make('fotos')->label('Fotos del problema')->image()->multiple()
                    ->directory('reclamos')->maxFiles(6)->imageEditor()->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('folio')->label('Folio')->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('cliente.nombre')->label('Cliente')->searchable()->placeholder('—'),
                Tables\Columns\TextColumn::make('producto')->label('Producto')->placeholder('—')->limit(28),
                Tables\Columns\TextColumn::make('tipo_problema')->label('Problema')->badge()
                    ->formatStateUsing(fn ($state) => $state ? ucfirst($state) : '—')->color('gray'),
                Tables\Columns\TextColumn::make('pedido_id')->label('Pedido')
                    ->formatStateUsing(fn ($state, Reclamo $record) => $record->pedido?->folio ?: ($state ? '#' . $state : '—'))
                    ->url(fn (Reclamo $record) => $record->pedido_id ? PedidoEspecialResource::getUrl('view', ['record' => $record->pedido_id]) : null)
                    ->color('primary')->openUrlInNewTab(),
                Tables\Columns\TextColumn::make('estado')->label('Estado')->badge()
                    ->formatStateUsing(fn ($state) => [
                        'abierto' => 'Abierto', 'en_revision' => 'En revisión', 'en_reparacion' => 'En reparación',
                        'resuelto' => 'Resuelto', 'rechazado' => 'Rechazado',
                    ][$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'abierto' => 'warning', 'en_revision' => 'info', 'en_reparacion' => 'primary',
                        'resuelto' => 'success', 'rechazado' => 'danger', default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('resolucion')->label('Resolución')->badge()
                    ->formatStateUsing(fn ($state) => [
                        'reparacion' => 'Reparación', 'reposicion' => 'Reposición/cambio',
                        'nota_credito' => 'Nota de crédito', 'reembolso' => 'Reembolso', 'sin_garantia' => 'Sin garantía',
                    ][$state] ?? '—')->color('gray')->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')->label('Fecha')->date('d/m/Y')->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('estado')->options([
                    'abierto' => 'Abierto', 'en_revision' => 'En revisión', 'en_reparacion' => 'En reparación',
                    'resuelto' => 'Resuelto', 'rechazado' => 'Rechazado',
                ]),
            ])
            ->recordUrl(fn (Reclamo $record) => Pages\ViewReclamo::getUrl(['record' => $record->id]))
            ->actions([
                Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReclamos::route('/'),
            'create' => Pages\CreateReclamo::route('/create'),
            'view' => Pages\ViewReclamo::route('/{record}'),
            'edit' => Pages\EditReclamo::route('/{record}/edit'),
        ];
    }
}
