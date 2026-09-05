<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VentaResource\Pages;
use App\Models\Venta;
use App\Support\Acl;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class VentaResource extends Resource
{
    protected static ?string $model = Venta::class;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['cliente', 'pedido']);
    }
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Ventas';
    protected static ?string $modelLabel = 'Venta';
    protected static ?string $pluralModelLabel = 'Ventas';
    protected static string|\UnitEnum|null $navigationGroup = 'Ventas';
    protected static ?int $navigationSort = 6;

    protected static function permitido(): bool
    {
        return Acl::esAdmin() || Acl::esOperaciones() || Acl::rol() === 'contabilidad' || Acl::esVendedor();
    }
    public static function shouldRegisterNavigation(): bool { return static::permitido(); }
    public static function canViewAny(): bool { return static::permitido(); }
    public static function canCreate(): bool { return false; }
    public static function canEdit(Model $record): bool { return false; }
    public static function canDelete(Model $record): bool { return false; }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('tipo_comprobante')->label('Tipo')->badge()
                    ->formatStateUsing(fn ($state) => $state === 'factura' ? 'Factura' : ($state === 'nota_venta' ? 'Nota de venta' : '—'))
                    ->color(fn ($state) => $state === 'factura' ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('numero_comprobante')->label('Número')->searchable()->weight('bold')
                    ->placeholder(fn ($record) => $record->nro_factura ?: '—'),
                Tables\Columns\TextColumn::make('fecha')->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('cliente.nombre')->label('Cliente')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('codigo_origen')->label('Origen')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('total')->money('USD')->sortable(),
                Tables\Columns\TextColumn::make('estado')->badge()->colors([
                    'success' => 'emitida',
                    'danger'  => 'anulada',
                ]),
                Tables\Columns\TextColumn::make('plazo_anulacion')->label('Plazo SRI')
                    ->state(function ($record) {
                        if ($record->tipo_comprobante !== 'factura' || $record->estado === 'anulada') return null;
                        if (! \App\Services\Sri\AnularFactura::fueraDePlazoAnulacion($record->fecha ?? $record->created_at)) return null;
                        return 'Fuera de plazo (solo NC)';
                    })
                    ->badge()->color('warning')->placeholder(''),
                Tables\Columns\TextColumn::make('pedido.folio')->label('Pedido')->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo_comprobante')->label('Tipo')
                    ->options(['factura' => 'Factura', 'nota_venta' => 'Nota de venta']),
                Tables\Filters\SelectFilter::make('forma_venta')->label('Forma')
                    ->options(['online' => 'Online', 'stock' => 'Stock', 'local' => 'Local']),
                Tables\Filters\Filter::make('rango')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('desde')->label('Desde'),
                        \Filament\Forms\Components\DatePicker::make('hasta')->label('Hasta'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['desde'] ?? null, fn ($q, $d) => $q->whereDate('fecha', '>=', $d))
                            ->when($data['hasta'] ?? null, fn ($q, $d) => $q->whereDate('fecha', '<=', $d));
                    }),
            ])
            ->recordUrl(fn (Venta $record) => Pages\ViewVenta::getUrl(['record' => $record->id]))
            ->actions([])
            ->bulkActions([]);
    }

    /** Ruta del PDF según tipo (pública para ViewVenta). */
    public static function rutaPdfPublica(Venta $venta): ?string
    {
        if ($venta->tipo_comprobante === 'factura') {
            $comp = $venta->sriComprobante;
            if ($comp && $comp->pdf_path) return $comp->pdf_path;
            if ($comp) return storage_path('app/sri/RIDE_' . $comp->clave_acceso . '.pdf');
            return null;
        }
        return storage_path('app/sri/NV_' . $venta->numero_comprobante . '.pdf');
    }

    /** Reenvía el comprobante por correo (pública para ViewVenta). */
    public static function reenviarPublico(Venta $venta): array
    {
        try {
            if ($venta->tipo_comprobante === 'factura') {
                $comp = $venta->sriComprobante;
                if (! $comp) return ['ok' => false, 'msg' => 'Sin comprobante SRI asociado.'];
                $r = \App\Services\Sri\EnviarComprobante::procesar($comp->fresh(), true);
                return ['ok' => $r['ok'] ?? false, 'msg' => $r['msg'] ?? ''];
            }
            $pdf = static::rutaPdfPublica($venta);
            if (! $pdf || ! is_file($pdf)) $pdf = \App\Services\Sri\NotaVenta::generar($venta);
            $cliente = $venta->cliente;
            if (! $cliente || ! $cliente->email) return ['ok' => false, 'msg' => 'El cliente no tiene correo.'];
            \App\Services\Sri\NotaVenta::enviar($venta->fresh(), $pdf);
            return ['ok' => true, 'msg' => 'Enviado a ' . $cliente->email];
        } catch (\Throwable $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVentas::route('/'),
            'view'  => Pages\ViewVenta::route('/{record}'),
        ];
    }
}
