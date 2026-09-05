<?php
namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;

use App\Support\Acl;
use App\Filament\Resources\IncentivoResource\Pages;
use App\Models\Incentivo;
use App\Models\Empleado;
use Filament\Forms; use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables; use Filament\Tables\Table;
use Filament\Notifications\Notification;

class IncentivoResource extends Resource
{
    protected static ?string $model = Incentivo::class;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['empleado']);
    }

    public static function canViewAny(): bool { return Acl::esAdmin(); }
    public static function canDelete($record): bool { return false; }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-gift';
    protected static string|\UnitEnum|null $navigationGroup = 'Nómina / RRHH';
    protected static ?string $modelLabel = 'Incentivo';
    protected static ?string $pluralModelLabel = 'Incentivos a colaboradores';
    protected static ?int $navigationSort = 8;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Colaborador')->columns(2)->schema([
                Forms\Components\Select::make('empleado_id')->label('Colaborador')->required()
                    ->options(fn () => Empleado::where('relacion', 'colaborador')->where('activo', true)->orderBy('nombre')->pluck('nombre', 'id'))
                    ->searchable()
                    ->helperText('Solo aparecen personas con vínculo "Colaborador" (sin relación de dependencia).'),
                Forms\Components\DatePicker::make('fecha')->required()->default(now()),
                Forms\Components\TextInput::make('concepto')->required()->columnSpanFull()
                    ->placeholder('Ej: incentivo por 4 artículos publicados en julio'),
            ]),
            \Filament\Schemas\Components\Section::make('Valores')->columns(3)->schema([
                Forms\Components\TextInput::make('monto')->label('Monto bruto')->numeric()->prefix('$')->required()->live(onBlur: true),
                Forms\Components\TextInput::make('ret_renta')->label('Retención renta (si aplica)')->numeric()->prefix('$')->default(0)->live(onBlur: true),
                Forms\Components\Placeholder::make('total_calc')->label('Total a entregar')
                    ->content(fn (\Filament\Schemas\Components\Utilities\Get $get) => '$' . number_format((float)$get('monto') - (float)$get('ret_renta'), 2)),
            ]),
            \Filament\Schemas\Components\Section::make('Respaldo del pago')->columns(2)->schema([
                Forms\Components\Select::make('metodo_pago')->label('Método')
                    ->options(['transferencia' => 'Transferencia', 'efectivo' => 'Efectivo', 'cheque' => 'Cheque'])->default('transferencia')->native(false)->live(),
                Forms\Components\TextInput::make('nro_comprobante')->label(fn (\Filament\Schemas\Components\Utilities\Get $get) => match ($get('metodo_pago')) {
                    'transferencia' => 'N° transacción',
                    'cheque' => 'N° cheque',
                    'efectivo' => 'N° recibo',
                    default => 'N° comprobante',
                }),
                Forms\Components\FileUpload::make('adjunto')->label('Comprobante (PDF/imagen)')->directory('incentivos')->disk('public')->columnSpanFull(),
                Forms\Components\Textarea::make('nota')->rows(2)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('folio')->searchable(),
            Tables\Columns\TextColumn::make('empleado.nombre')->label('Colaborador')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('fecha')->date('d/m/Y')->sortable(),
            Tables\Columns\TextColumn::make('concepto')->wrap()->limit(40),
            Tables\Columns\TextColumn::make('total')->money('usd')->weight('bold'),
            Tables\Columns\TextColumn::make('estado')->badge()->color(fn ($state) => $state === 'anulado' ? 'danger' : 'success'),
        ])
        ->defaultSort('id', 'desc')
        ->actions([
            Actions\Action::make('pdf')->label('Descargar PDF')->icon('heroicon-o-document-arrow-down')->color('gray')
                ->action(function (Incentivo $r) {
                    $path = \App\Services\PdfNomina::incentivo($r);
                    return response()->download($path, 'incentivo-' . ($r->folio ?: $r->id) . '.pdf');
                }),
            Actions\Action::make('anular')->label('Anular')->icon('heroicon-o-x-circle')->color('danger')
                ->visible(fn (Incentivo $r) => $r->estado !== 'anulado')
                ->requiresConfirmation()->modalDescription('Anula el incentivo y reversa su asiento contable. No se borra.')
                ->action(function (Incentivo $r) {
                    $r->update(['estado' => 'anulado']);
                    \App\Services\ContabilidadAuto::reversarDe('Incentivo', $r->id);
                    Notification::make()->success()->title('Incentivo anulado')->send();
                }),
        ])
        ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListIncentivos::route('/'),
            'create' => Pages\CreateIncentivo::route('/create'),
        ];
    }
}
