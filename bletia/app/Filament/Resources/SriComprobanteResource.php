<?php
namespace App\Filament\Resources;

use App\Support\Acl;
use App\Filament\Resources\SriComprobanteResource\Pages;
use App\Models\SriComprobante;
use Filament\Actions;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SriComprobanteResource extends Resource
{
    protected static ?string $model = SriComprobante::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-check';
    protected static string|\UnitEnum|null $navigationGroup = 'Contabilidad';
    protected static ?string $modelLabel = 'Comprobante SRI';
    protected static ?string $pluralModelLabel = 'Comprobantes SRI (historial)';
    protected static ?int $navigationSort = 20;

    public static function canViewAny(): bool { return Acl::esAdmin() || Acl::esContabilidad(); }
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('tipo')->label('Tipo')->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'factura' => 'Factura',
                        'nota_credito' => 'Nota de crédito',
                        'guia_remision' => 'Guía de remisión',
                        'retencion' => 'Retención',
                        default => ucfirst($state),
                    })
                    ->color(fn ($state) => match ($state) {
                        'factura' => 'success', 'nota_credito' => 'danger', 'guia_remision' => 'info', default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('numero')->label('Número')->weight('bold')
                    ->state(fn (SriComprobante $r) => $r->estab . '-' . $r->pto_emi . '-' . $r->secuencial),
                Tables\Columns\TextColumn::make('receptor_razon')->label('Receptor')->searchable()->placeholder('—'),
                Tables\Columns\TextColumn::make('receptor_identificacion')->label('Identificación')->searchable()->placeholder('—'),
                Tables\Columns\TextColumn::make('estado')->badge()
                    ->color(fn ($state) => match ($state) {
                        'AUTORIZADO' => 'success',
                        'NO_AUTORIZADO', 'ERROR', 'DEVUELTA' => 'danger',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('total')->money('USD')->sortable(),
                Tables\Columns\TextColumn::make('ambiente')->label('Ambiente')->badge()
                    ->formatStateUsing(fn ($state) => $state === '2' ? 'Producción' : 'Pruebas')
                    ->color(fn ($state) => $state === '2' ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('numero_autorizacion')->label('N° autorización')->toggleable(isToggledHiddenByDefault: true)->placeholder('—'),
                Tables\Columns\TextColumn::make('clave_acceso')->label('Clave de acceso')->toggleable(isToggledHiddenByDefault: true)->copyable(),
                Tables\Columns\TextColumn::make('fecha_autorizacion')->label('Autorizado')->dateTime('d/m/Y H:i')->placeholder('—')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Emitido')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')->options([
                    'factura' => 'Factura', 'nota_credito' => 'Nota de crédito', 'guia_remision' => 'Guía de remisión', 'retencion' => 'Retención',
                ]),
                Tables\Filters\SelectFilter::make('estado')->options([
                    'CREADO' => 'Creado', 'FIRMADO' => 'Firmado', 'RECIBIDA' => 'Recibida',
                    'DEVUELTA' => 'Devuelta', 'AUTORIZADO' => 'Autorizado', 'NO_AUTORIZADO' => 'No autorizado', 'ERROR' => 'Error',
                ]),
                Tables\Filters\SelectFilter::make('ambiente')->options(['1' => 'Pruebas', '2' => 'Producción']),
            ])
            ->actions([
                Actions\Action::make('xml')->label('Ver XML')->icon('heroicon-o-code-bracket')->color('gray')
                    ->visible(fn (SriComprobante $r) => filled($r->xml_autorizado) || filled($r->xml_firmado))
                    ->action(function (SriComprobante $r) {
                        return response($r->xml_autorizado ?: $r->xml_firmado, 200, ['Content-Type' => 'application/xml']);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListSriComprobantes::route('/')];
    }
}
