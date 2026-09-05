<?php
namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;

use App\Support\Acl;
use App\Filament\Resources\GastoResource\Pages;
use App\Models\Gasto;
use App\Models\Proveedor;
use Filament\Forms; use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables; use Filament\Tables\Table;

class GastoResource extends Resource
{
    protected static ?string $model = Gasto::class;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['proveedor']);
    }

    public static function canViewAny(): bool { return Acl::puedeContabilidad(); }
    public static function canDelete($record): bool { return false; } // se anula, no se borra

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';
    protected static string|\UnitEnum|null $navigationGroup = 'Contabilidad';
    protected static ?string $modelLabel = 'Gasto';
    protected static ?string $pluralModelLabel = 'Gastos';
    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Gasto')->columns(2)->columnSpanFull()->schema([
                Forms\Components\DatePicker::make('fecha')->required()->default(now()),
                Forms\Components\Select::make('categoria')->required()->options(Gasto::CATEGORIAS)->searchable()->native(false),
                Forms\Components\Select::make('proveedor_id')->label('Proveedor (si está registrado)')
                    ->options(fn () => Proveedor::orderBy('nombre')->pluck('nombre', 'id'))->searchable()->live()
                    ->helperText('O escribe el beneficiario abajo si no es un proveedor fijo.'),
                Forms\Components\TextInput::make('beneficiario')->label('Beneficiario (si no es proveedor)'),
                Forms\Components\TextInput::make('beneficiario_id_num')->label('RUC / Cédula del beneficiario')->maxLength(20),
            ]),
            \Filament\Schemas\Components\Section::make('Documento de respaldo')->columns(3)->columnSpanFull()->schema([
                Forms\Components\Select::make('doc_tipo')->label('Tipo')->options([
                    'factura' => 'Factura', 'nota_venta' => 'Nota de venta', 'liquidacion' => 'Liquidación', 'recibo' => 'Recibo',
                ])->native(false),
                Forms\Components\TextInput::make('doc_numero')->label('N° documento'),
                Forms\Components\TextInput::make('autorizacion_sri')->label('Autorización SRI'),
                Forms\Components\FileUpload::make('adjunto')->label('Adjuntar factura (PDF/imagen)')->directory('gastos')->disk('public')->columnSpanFull(),
            ]),
            \Filament\Schemas\Components\Section::make('Valores')->columns(3)->columnSpanFull()->schema([
                Forms\Components\TextInput::make('base')->label('Base (sin IVA)')->numeric()->prefix('$')->required()->default(0)->live(onBlur: true),
                Forms\Components\TextInput::make('iva')->label('IVA')->numeric()->prefix('$')->default(0)->live(onBlur: true),
                Forms\Components\Placeholder::make('total_calc')->label('Total')
                    ->content(fn (\Filament\Schemas\Components\Utilities\Get $get) => '$' . number_format((float)$get('base') + (float)$get('iva') - (float)$get('ret_iva') - (float)$get('ret_renta'), 2)),
                Forms\Components\TextInput::make('ret_iva')->label('Retención IVA')->numeric()->prefix('$')->default(0),
                Forms\Components\TextInput::make('ret_renta')->label('Retención Renta')->numeric()->prefix('$')->default(0),
            ]),
            \Filament\Schemas\Components\Section::make('Pago')->columns(2)->columnSpanFull()->schema([
                Forms\Components\Select::make('forma_pago')->label('Forma de pago')->required()->default('contado')
                    ->options(['contado' => 'Al momento', 'credito' => 'A crédito (queda por pagar)'])->live()->native(false),
                Forms\Components\Select::make('metodo_pago')->label('Método')
                    ->options(['efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'tarjeta' => 'Tarjeta', 'cheque' => 'Cheque'])
                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('forma_pago') === 'contado')->native(false),
                Forms\Components\Textarea::make('notas')->rows(2)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('fecha')->date('d/m/Y')->sortable(),
            Tables\Columns\TextColumn::make('folio')->searchable(),
            Tables\Columns\TextColumn::make('categoria')->badge()->formatStateUsing(fn ($state) => Gasto::CATEGORIAS[$state] ?? $state),
            Tables\Columns\TextColumn::make('proveedor.nombre')->label('Proveedor')->placeholder(fn ($record) => $record->beneficiario ?: '—'),
            Tables\Columns\TextColumn::make('total')->money('usd')->sortable(),
            Tables\Columns\TextColumn::make('forma_pago')->badge()->formatStateUsing(fn ($state) => $state === 'credito' ? 'Crédito' : 'Contado'),
            Tables\Columns\TextColumn::make('estado')->badge()->color(fn ($state) => $state === 'anulado' ? 'danger' : 'success'),
        ])
        ->defaultSort('id', 'desc')
        ->filters([
            Tables\Filters\SelectFilter::make('categoria')->options(Gasto::CATEGORIAS),
        ])
        ->actions([
            Actions\Action::make('pdf')->label('Comprobante PDF')->icon('heroicon-o-document-arrow-down')->color('gray')
                ->action(function ($record) {
                    $path = \App\Services\PdfContable::gasto($record);
                    return response()->download($path, 'egreso-'.($record->folio ?: $record->id).'.pdf');
                }),
            Actions\Action::make('anular')->label('Anular')->icon('heroicon-o-x-circle')->color('danger')
                ->visible(fn (Gasto $r) => $r->estado !== 'anulado')
                ->requiresConfirmation()->modalDescription('Anula el gasto y reversa su asiento contable. No se borra.')
                ->action(function (Gasto $r) {
                    $r->update(['estado' => 'anulado']);
                    \App\Services\ContabilidadAuto::reversarDe('Gasto', $r->id);
                    \Filament\Notifications\Notification::make()->success()->title('Gasto anulado')->send();
                }),
        ])
        ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListGastos::route('/'),
            'create' => Pages\CreateGasto::route('/create'),
            'edit'   => Pages\EditGasto::route('/{record}/edit'),
        ];
    }
}
