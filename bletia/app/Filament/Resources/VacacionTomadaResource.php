<?php
namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;

use App\Support\Acl;
use App\Services\VacacionesControl;
use App\Filament\Resources\VacacionTomadaResource\Pages;
use App\Models\VacacionTomada;
use App\Models\Empleado;
use Filament\Forms; use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables; use Filament\Tables\Table;
use Filament\Notifications\Notification;

class VacacionTomadaResource extends Resource
{
    protected static ?string $model = VacacionTomada::class;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['empleado']);
    }

    public static function canViewAny(): bool { return Acl::esAdmin(); }
    public static function canDelete($record): bool { return false; }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-sun';
    protected static string|\UnitEnum|null $navigationGroup = 'Nómina / RRHH';
    protected static ?string $modelLabel = 'Vacaciones tomadas';
    protected static ?string $pluralModelLabel = 'Vacaciones tomadas';
    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('empleado_id')->label('Empleado')->required()
                ->options(fn () => Empleado::where('activo', true)->where('relacion', 'dependencia')->orderBy('nombre')->pluck('nombre', 'id'))
                ->searchable()->live()
                ->afterStateUpdated(function ($state, \Filament\Schemas\Components\Utilities\Set $set) {
                    $emp = Empleado::find($state);
                    if ($emp) $set('pendientes_info', VacacionesControl::diasPendientes($emp) . ' días pendientes a hoy.');
                }),
            Forms\Components\Placeholder::make('pendientes_info')->label('Saldo actual')
                ->content(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('pendientes_info') ?: 'Elige un empleado para ver su saldo.'),
            \Filament\Schemas\Components\Grid::make(3)->schema([
                Forms\Components\DatePicker::make('fecha_inicio')->required()->live(onBlur: true)
                    ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get) {
                        self::calcularDias($set, $get);
                    }),
                Forms\Components\DatePicker::make('fecha_fin')->required()->live(onBlur: true)
                    ->afterStateUpdated(function (\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get) {
                        self::calcularDias($set, $get);
                    }),
                Forms\Components\TextInput::make('dias')->label('Días (calendario, ajustable)')->numeric()->required()
                    ->helperText('Se calcula solo (inicio a fin, inclusive). Editable si negociaron distinto.'),
            ]),
            Forms\Components\Textarea::make('nota')->rows(2)->columnSpanFull(),
            Forms\Components\FileUpload::make('adjunto')->label('Solicitud/autorización firmada (opcional)')->directory('vacaciones')->disk('public')->columnSpanFull(),
        ]);
    }

    protected static function calcularDias(\Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get): void
    {
        $ini = $get('fecha_inicio'); $fin = $get('fecha_fin');
        if (! $ini || ! $fin) return;
        $i = \Illuminate\Support\Carbon::parse($ini); $f = \Illuminate\Support\Carbon::parse($fin);
        if ($f->lt($i)) return;
        $set('dias', $i->diffInDays($f) + 1); // inclusive, calendario (Art. 69)
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('folio')->searchable(),
            Tables\Columns\TextColumn::make('empleado.nombre')->label('Empleado')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('fecha_inicio')->date('d/m/Y')->sortable(),
            Tables\Columns\TextColumn::make('fecha_fin')->date('d/m/Y'),
            Tables\Columns\TextColumn::make('dias')->label('Días')->suffix(' d'),
            Tables\Columns\TextColumn::make('estado')->badge()->color(fn ($state) => $state === 'anulada' ? 'danger' : 'success'),
        ])
        ->defaultSort('fecha_inicio', 'desc')
        ->actions([
            Actions\Action::make('pdf')->label('Descargar PDF')->icon('heroicon-o-document-arrow-down')->color('gray')
                ->action(function (VacacionTomada $r) {
                    $path = \App\Services\PdfNomina::vacacion($r);
                    return response()->download($path, 'vacaciones-' . ($r->folio ?: $r->id) . '.pdf');
                }),
            Actions\Action::make('anular')->label('Anular')->icon('heroicon-o-x-circle')->color('danger')
                ->visible(fn (VacacionTomada $r) => $r->estado !== 'anulada')
                ->requiresConfirmation()->modalDescription('Anula el registro y devuelve los días al saldo del empleado.')
                ->action(function (VacacionTomada $r) {
                    $r->update(['estado' => 'anulada']);
                    Notification::make()->success()->title('Vacación anulada, días devueltos al saldo')->send();
                }),
        ])
        ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListVacacionTomadas::route('/'),
            'create' => Pages\CreateVacacionTomada::route('/create'),
        ];
    }
}
