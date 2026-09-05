<?php
namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;

use App\Support\Acl;
use App\Services\Nomina;
use App\Filament\Resources\RolPagoResource\Pages;
use App\Models\RolPago;
use App\Models\Empleado;
use Filament\Forms; use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables; use Filament\Tables\Table;
use Filament\Notifications\Notification;

class RolPagoResource extends Resource
{
    protected static ?string $model = RolPago::class;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['empleado']);
    }

    public static function canViewAny(): bool { return Acl::esAdmin(); }
    public static function canDelete($record): bool { return false; }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-currency-dollar';
    protected static string|\UnitEnum|null $navigationGroup = 'Nómina / RRHH';
    protected static ?string $modelLabel = 'Rol de pago';
    protected static ?string $pluralModelLabel = 'Roles de pago';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Tabs::make('RolPago')->columnSpanFull()->tabs([

                \Filament\Schemas\Components\Tabs\Tab::make('Periodo')->columns(3)->schema([
                    Forms\Components\Select::make('empleado_id')->label('Empleado')->required()
                        ->options(fn () => Empleado::where('activo', true)->whereIn('relacion', ['dependencia','honorarios'])->orderBy('nombre')->pluck('nombre', 'id'))
                        ->searchable()->live()
                        ->afterStateUpdated(function ($state, \Filament\Schemas\Components\Utilities\Set $set) {
                            $emp = Empleado::find($state);
                            if ($emp) $set('sueldo', $emp->sueldo);
                        }),
                    Forms\Components\Select::make('anio')->label('Año')->required()->default(now()->year)
                        ->options(collect(range(now()->year - 2, now()->year + 1))->mapWithKeys(fn ($y) => [$y => $y])),
                    Forms\Components\Select::make('mes')->label('Mes')->required()->default(now()->month)
                        ->options([1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre']),
                ]),

                \Filament\Schemas\Components\Tabs\Tab::make('Ingresos')->columns(3)->schema([
                    Forms\Components\TextInput::make('sueldo')->label('Sueldo / honorario')->numeric()->prefix('$')->default(0)->required()
                        ->disabled()->dehydrated()
                        ->helperText('Se toma del empleado; no se edita aquí.'),
                    Forms\Components\TextInput::make('horas_suplementarias')->label('Horas suplementarias (50%)')->numeric()->default(0)->minValue(0)
                        ->suffix('h')->helperText('Diurnas hasta 4h/día. Se calcula solo.'),
                    Forms\Components\TextInput::make('horas_extraordinarias')->label('Horas extraordinarias (100%)')->numeric()->default(0)->minValue(0)
                        ->suffix('h')->helperText('Noches, fines de semana y feriados. Se calcula solo.'),
                    Forms\Components\Hidden::make('horas_extra')->default(0),
                    Forms\Components\TextInput::make('comisiones')->numeric()->prefix('$')->default(0),
                    Forms\Components\TextInput::make('bonos')->numeric()->prefix('$')->default(0),
                    Forms\Components\TextInput::make('otros_ingresos')->label('Otros ingresos')->numeric()->prefix('$')->default(0),
                ]),

                \Filament\Schemas\Components\Tabs\Tab::make('Descuentos')->columns(3)->schema([
                    Forms\Components\TextInput::make('anticipos')->numeric()->prefix('$')->default(0),
                    Forms\Components\TextInput::make('prestamos_iess')->label('Préstamos IESS')->numeric()->prefix('$')->default(0),
                    Forms\Components\TextInput::make('otros_descuentos')->label('Otros descuentos')->numeric()->prefix('$')->default(0),
                    Forms\Components\TextInput::make('ret_renta')->label('Retención renta')->numeric()->prefix('$')->default(0)
                        ->helperText('Honorarios: retención renta que aplicas al pagar. Dependencia: IR proyectado, si aplica.'),
                    \Filament\Schemas\Components\Text::make('El aporte personal IESS (9,45%) se calcula solo al guardar; no lo pongas aquí.')->columnSpanFull(),
                ]),

            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('folio')->searchable(),
            Tables\Columns\TextColumn::make('empleado.nombre')->label('Empleado')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('periodo')->label('Periodo')
                ->getStateUsing(fn (RolPago $r) => $r->nombreMes() . ' ' . $r->anio),
            Tables\Columns\TextColumn::make('total_ingresos')->money('usd')->label('Ingresos'),
            Tables\Columns\TextColumn::make('total_descuentos')->money('usd')->label('Descuentos'),
            Tables\Columns\TextColumn::make('liquido')->money('usd')->label('Líquido')->weight('bold'),
            Tables\Columns\TextColumn::make('costo_empresa')->money('usd')->label('Costo empresa')->toggleable(),
            Tables\Columns\TextColumn::make('estado')->badge()->color(fn ($state) => match ($state) {
                'pagado' => 'success', 'anulado' => 'danger', default => 'gray',
            }),
        ])
        ->defaultSort('id', 'desc')
        ->filters([
            Tables\Filters\SelectFilter::make('anio')->options(collect(range(now()->year - 2, now()->year + 1))->mapWithKeys(fn ($y) => [$y => $y])),
            Tables\Filters\SelectFilter::make('estado')->options(['borrador' => 'Borrador', 'pagado' => 'Pagado', 'anulado' => 'Anulado']),
        ])
        ->actions([
            Actions\ViewAction::make(),
            Actions\EditAction::make()->visible(fn (RolPago $r) => $r->estado === 'borrador'),
            Actions\Action::make('pagar')->label('Marcar pagado')->icon('heroicon-o-check-circle')->color('success')
                ->visible(fn (RolPago $r) => $r->estado === 'borrador')
                ->form([
                    Forms\Components\DatePicker::make('fecha_pago')->required()->default(now()),
                    Forms\Components\Select::make('metodo_pago')->options(['transferencia' => 'Transferencia', 'efectivo' => 'Efectivo', 'cheque' => 'Cheque'])->default('transferencia')->native(false)->live(),
                    Forms\Components\TextInput::make('nro_comprobante_pago')->label('N° comprobante / transferencia')->maxLength(40)
                        ->helperText('N° de la transferencia, cheque o recibo del pago.'),
                    Forms\Components\TextInput::make('banco_pago')->label('Banco')->maxLength(80)
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => in_array($get('metodo_pago'), ['transferencia', 'cheque'], true)),
                    Forms\Components\FileUpload::make('adjunto_pago')->label('Adjuntar comprobante (PDF/imagen)')->directory('nomina-pagos')->disk('public'),
                    Forms\Components\Textarea::make('nota_pago')->label('Nota / detalle del pago')->rows(2)
                        ->helperText('Opcional. Ej: pago directo del dueño, adelanto acordado, etc.'),
                ])
                ->action(function (RolPago $r, array $data) {
                    $r->update([
                        'estado' => 'pagado',
                        'fecha_pago' => $data['fecha_pago'],
                        'metodo_pago' => $data['metodo_pago'],
                        'nro_comprobante_pago' => $data['nro_comprobante_pago'] ?? null,
                        'banco_pago' => $data['banco_pago'] ?? null,
                        'adjunto_pago' => $data['adjunto_pago'] ?? null,
                        'nota_pago' => $data['nota_pago'] ?? null,
                    ]);
                    Nomina::asentar($r->fresh('empleado'));
                    Notification::make()->success()->title('Rol pagado y contabilizado')->send();
                }),
            Actions\Action::make('pdf')->label('Descargar PDF')->icon('heroicon-o-document-arrow-down')->color('gray')
                ->action(function (RolPago $r) {
                    $path = \App\Services\PdfNomina::rol($r);
                    return response()->download($path, 'rol-'.($r->folio ?: $r->id).'.pdf');
                }),
            Actions\Action::make('anular')->label('Anular')->icon('heroicon-o-x-circle')->color('danger')
                ->visible(fn (RolPago $r) => $r->estado !== 'anulado')
                ->requiresConfirmation()->modalDescription('Anula el rol y reversa su asiento si estaba contabilizado.')
                ->action(function (RolPago $r) {
                    $r->update(['estado' => 'anulado']);
                    \App\Services\ContabilidadAuto::reversarDe('RolPago', $r->id);
                    Notification::make()->success()->title('Rol anulado')->send();
                }),
        ])
        ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRolesPago::route('/'),
            'create' => Pages\CreateRolPago::route('/create'),
            'edit'   => Pages\EditRolPago::route('/{record}/edit'),
            'view'   => Pages\ViewRolPago::route('/{record}'),
        ];
    }
}
