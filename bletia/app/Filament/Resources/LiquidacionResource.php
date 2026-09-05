<?php
namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;

use App\Support\Acl;
use App\Services\AcumuladosBeneficios;
use App\Services\PagosBeneficio;
use App\Filament\Resources\LiquidacionResource\Pages;
use App\Models\Liquidacion;
use App\Models\Empleado;
use Filament\Forms; use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables; use Filament\Tables\Table;
use Filament\Notifications\Notification;

class LiquidacionResource extends Resource
{
    protected static ?string $model = Liquidacion::class;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['empleado']);
    }

    public static function canViewAny(): bool { return Acl::esAdmin(); }
    public static function canDelete($record): bool { return false; }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-right-start-on-rectangle';
    protected static string|\UnitEnum|null $navigationGroup = 'Nómina / RRHH';
    protected static ?string $modelLabel = 'Liquidación';
    protected static ?string $pluralModelLabel = 'Liquidaciones';
    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Tabs::make('Liquidacion')->columnSpanFull()->tabs([

                \Filament\Schemas\Components\Tabs\Tab::make('Empleado y salida')->columns(2)->schema([
                    Forms\Components\Select::make('empleado_id')->label('Empleado')->required()
                        ->options(fn () => Empleado::where('relacion', 'dependencia')->orderBy('nombre')->pluck('nombre', 'id'))
                        ->searchable()->live()
                        ->afterStateUpdated(fn ($state, \Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get) => static::recalcular($state, $get, $set)),
                    Forms\Components\DatePicker::make('fecha')->label('Fecha de liquidación')->required()->default(now()),
                    Forms\Components\DatePicker::make('fecha_salida')->required()->default(now())->live()
                        ->afterStateUpdated(fn ($state, \Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get) => static::recalcular($get('empleado_id'), $get, $set)),
                    Forms\Components\Select::make('motivo')->options([
                        'renuncia' => 'Renuncia', 'despido' => 'Despido (intempestivo/injustificado)', 'mutuo_acuerdo' => 'Mutuo acuerdo', 'fin_contrato' => 'Fin de contrato',
                    ])->native(false)->live()
                        ->afterStateUpdated(fn ($state, \Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get) => static::recalcular($get('empleado_id'), $get, $set))
                        ->helperText('El motivo cambia el monto: solo "Despido" incluye indemnización Art. 188 + bonificación Art. 185.'),
                ]),

                \Filament\Schemas\Components\Tabs\Tab::make('Beneficios acumulados')->columns(3)->schema([
                    Forms\Components\TextInput::make('decimo_tercero')->numeric()->prefix('$')->default(0)->live(onBlur: true),
                    Forms\Components\TextInput::make('decimo_cuarto')->numeric()->prefix('$')->default(0)->live(onBlur: true),
                    Forms\Components\TextInput::make('vacaciones')->numeric()->prefix('$')->default(0)->live(onBlur: true),
                    Forms\Components\TextInput::make('fondos_reserva')->numeric()->prefix('$')->default(0)->live(onBlur: true),
                    Forms\Components\Textarea::make('auditoria_texto')->label('De dónde sale cada cifra (auditable)')->disabled()->dehydrated(false)->rows(5)->columnSpanFull()
                        ->helperText('Se calcula desde tus roles de pago reales y lo que ya hayas pagado. Si algo no cuadra, revisa el rol o el pago de beneficio correspondiente.'),
                ]),

                \Filament\Schemas\Components\Tabs\Tab::make('Indemnización')->columns(4)->schema([
                    Forms\Components\TextInput::make('anios_servicio')->label('Años (redondeo legal Art. 188)')->numeric()->disabled()->dehydrated()
                        ->helperText('Fracción de año cuenta como año completo, solo para esta fórmula.'),
                    Forms\Components\TextInput::make('tiempo_servicio')->label('Tiempo de servicio REAL')->disabled()->dehydrated()->columnSpan(2)
                        ->helperText('Años, meses y días exactos — el que va en el acta de finiquito.'),
                    Forms\Components\TextInput::make('mejor_remuneracion')->label('Mejor remuneración histórica')->numeric()->prefix('$')->disabled()->dehydrated(),
                    Forms\Components\TextInput::make('indemnizacion')->label('Indemnización Art. 188')->numeric()->prefix('$')->default(0)->live(onBlur: true)
                        ->helperText('<3 años: 3 sueldos. >=3 años: 1 sueldo x año (fracción = año completo).'),
                    Forms\Components\TextInput::make('bonificacion_desahucio')->label('Bonificación Art. 185')->numeric()->prefix('$')->default(0)->live(onBlur: true)
                        ->helperText('25% del sueldo actual x año completo de servicio.'),
                ]),

                \Filament\Schemas\Components\Tabs\Tab::make('Otros y total')->columns(3)->schema([
                    Forms\Components\TextInput::make('otros')->label('Otros haberes')->numeric()->prefix('$')->default(0)->live(onBlur: true),
                    Forms\Components\TextInput::make('descuentos')->label('Descuentos')->numeric()->prefix('$')->default(0)->live(onBlur: true),
                    Forms\Components\Placeholder::make('total_calc')->label('Total a pagar')
                        ->content(fn (\Filament\Schemas\Components\Utilities\Get $get) => '$' . number_format((float)$get('decimo_tercero') + (float)$get('decimo_cuarto') + (float)$get('vacaciones') + (float)$get('fondos_reserva') + (float)$get('indemnizacion') + (float)$get('bonificacion_desahucio') + (float)$get('otros') - (float)$get('descuentos'), 2)),
                ]),

                \Filament\Schemas\Components\Tabs\Tab::make('Respaldo')->schema([
                    Forms\Components\Textarea::make('detalle')->rows(2),
                    Forms\Components\FileUpload::make('adjunto')->label('Acta de finiquito / comprobante')->directory('liquidaciones')->disk('public'),
                ]),

            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('folio')->searchable(),
            Tables\Columns\TextColumn::make('empleado.nombre')->label('Empleado')->searchable(),
            Tables\Columns\TextColumn::make('fecha_salida')->date('d/m/Y'),
            Tables\Columns\TextColumn::make('motivo')->badge(),
            Tables\Columns\TextColumn::make('total')->money('usd')->weight('bold'),
            Tables\Columns\TextColumn::make('estado')->badge()->color(fn ($state) => $state === 'anulada' ? 'danger' : 'success'),
        ])
        ->defaultSort('id', 'desc')
        ->actions([
            Actions\Action::make('pdf')->label('Descargar PDF')->icon('heroicon-o-document-arrow-down')->color('gray')
                ->action(function (Liquidacion $r) {
                    $path = \App\Services\PdfNomina::liquidacion($r);
                    return response()->download($path, 'liquidacion-'.($r->folio ?: $r->id).'.pdf');
                }),
            Actions\Action::make('anular')->label('Anular')->icon('heroicon-o-x-circle')->color('danger')
                ->visible(fn (Liquidacion $r) => $r->estado !== 'anulada')
                ->requiresConfirmation()
                ->action(function (Liquidacion $r) {
                    $r->update(['estado' => 'anulada']);
                    \App\Services\ContabilidadAuto::reversarDe('Liquidacion', $r->id);
                    Notification::make()->success()->title('Liquidación anulada')->send();
                }),
        ])
        ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListLiquidaciones::route('/'),
            'create' => Pages\CreateLiquidacion::route('/create'),
        ];
    }

    protected static function recalcular(?int $empleadoId, \Filament\Schemas\Components\Utilities\Get $get, \Filament\Schemas\Components\Utilities\Set $set): void
    {
        $emp = $empleadoId ? Empleado::find($empleadoId) : null;
        if (! $emp) return;
        $fechaSalida = $get('fecha_salida') ?: now()->toDateString();
        $motivo = $get('motivo') ?: '';

        $acum = AcumuladosBeneficios::liquidacion($emp, $fechaSalida);
        $set('decimo_tercero', $acum['decimo_tercero']);
        $set('decimo_cuarto', $acum['decimo_cuarto']);
        $set('vacaciones', $acum['vacaciones']);
        $set('fondos_reserva', $acum['fondos_reserva']);

        $ind = \App\Services\IndemnizacionLaboral::calcular($emp, $motivo, $fechaSalida);
        $set('indemnizacion', $ind['indemnizacion']);
        $set('bonificacion_desahucio', $ind['bonificacion_desahucio']);
        $set('anios_servicio', $ind['anios_servicio']);
        $set('mejor_remuneracion', $ind['mejor_remuneracion']);

        $tiempo = \App\Services\IndemnizacionLaboral::tiempoServicio($emp, $fechaSalida);
        $set('tiempo_servicio', $tiempo['texto']);

        // Desglose auditable: de dónde sale cada cifra, para que se pueda verificar.
        $det = $acum['detalle'] ?? [];
        $lineas = [];
        $lineas[] = "Basado en {$acum['n_roles']} rol(es) de pago registrados de este empleado.";
        foreach (['decimo_tercero' => 'Décimo tercero', 'decimo_cuarto' => 'Décimo cuarto', 'fondos_reserva' => 'Fondos de reserva', 'vacaciones' => 'Vacaciones'] as $k => $lbl) {
            $d = $det[$k] ?? null;
            if (! $d) continue;
            $lineas[] = "{$lbl} (modo: {$d['modo']}): generado \$" . number_format($d['bruto'], 2) . " − ya pagado \$" . number_format($d['pagado'], 2) . " = pendiente \$" . number_format($d['pendiente'], 2);
        }
        $set('auditoria_texto', implode("\n", $lineas));
    }
}
