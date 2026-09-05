<?php
namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;

use App\Support\Acl;
use App\Services\Contabilidad;
use App\Filament\Resources\AsientoResource\Pages;
use App\Models\Asiento;
use App\Models\Cuenta;
use Filament\Forms; use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables; use Filament\Tables\Table;
use Filament\Notifications\Notification;

class AsientoResource extends Resource
{
    protected static ?string $model = Asiento::class;

    public static function canViewAny(): bool { return Acl::puedeContabilidad(); }
    public static function canEdit($record): bool { return false; }   // no se editan: se reversan
    public static function canDelete($record): bool { return false; } // nunca se borran

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';
    protected static string|\UnitEnum|null $navigationGroup = 'Contabilidad';
    protected static ?string $modelLabel = 'Asiento';
    protected static ?string $pluralModelLabel = 'Libro diario';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Grid::make(3)->schema([
                Forms\Components\DatePicker::make('fecha')->required()->default(now()),
                Forms\Components\TextInput::make('glosa')->label('Glosa / descripción')->required()->columnSpan(2),
            ]),
            Forms\Components\Repeater::make('lineas_tmp')->label('Movimientos')
                ->schema([
                    Forms\Components\Select::make('cuenta_id')->label('Cuenta')->required()
                        ->options(fn () => Cuenta::where('es_movimiento', true)->where('activo', true)->orderBy('codigo')
                            ->get()->mapWithKeys(fn ($c) => [$c->id => $c->codigo . ' · ' . $c->nombre]))
                        ->searchable()->columnSpan(2),
                    Forms\Components\TextInput::make('debe')->numeric()->default(0)->prefix('$')->live(onBlur: true),
                    Forms\Components\TextInput::make('haber')->numeric()->default(0)->prefix('$')->live(onBlur: true),
                ])->columns(4)->minItems(2)->defaultItems(2)
                ->live()
                ->helperText('El total del Debe debe ser igual al total del Haber.'),
            Forms\Components\Placeholder::make('cuadre')
                ->label('Cuadre')
                ->content(function (\Filament\Schemas\Components\Utilities\Get $get) {
                    $d = 0; $h = 0;
                    foreach ($get('lineas_tmp') ?? [] as $l) { $d += (float) ($l['debe'] ?? 0); $h += (float) ($l['haber'] ?? 0); }
                    $dif = round($d - $h, 2);
                    return $dif === 0.0
                        ? '✓ Cuadra · Debe $' . number_format($d, 2) . ' = Haber $' . number_format($h, 2)
                        : '✗ Descuadre $' . number_format(abs($dif), 2) . ' · Debe $' . number_format($d, 2) . ' / Haber $' . number_format($h, 2);
                }),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('numero')->label('N°')->searchable(),
            Tables\Columns\TextColumn::make('fecha')->date('d/m/Y')->sortable(),
            Tables\Columns\TextColumn::make('glosa')->wrap()->searchable(),
            Tables\Columns\TextColumn::make('origen')->badge()->color(fn ($state) => $state === 'manual' ? 'gray' : ($state === 'reverso' ? 'danger' : 'success')),
            Tables\Columns\TextColumn::make('debe')->money('usd')->label('Debe'),
            Tables\Columns\TextColumn::make('estado')->badge()->color(fn ($state) => $state === 'anulado' ? 'danger' : 'success'),
        ])
        ->defaultSort('id', 'desc')
        ->actions([
            Actions\Action::make('pdf')->label('Comprobante PDF')->icon('heroicon-o-document-arrow-down')->color('gray')
                ->action(function ($record) {
                    $path = \App\Services\PdfContable::asiento($record);
                    return response()->download($path, 'asiento-'.($record->numero ?: $record->id).'.pdf');
                }),
            Actions\ViewAction::make(),
            Actions\Action::make('reversar')->label('Reversar')->icon('heroicon-o-arrow-uturn-left')->color('danger')
                ->visible(fn (Asiento $r) => $r->estado === 'registrado')
                ->requiresConfirmation()
                ->modalDescription('Crea un asiento contrario que anula este. El original se conserva (no se borra).')
                ->action(function (Asiento $r) {
                    try {
                        Contabilidad::reversar($r);
                        Notification::make()->success()->title('Asiento reversado')->send();
                    } catch (\Throwable $e) {
                        Notification::make()->danger()->title('No se pudo reversar')->body($e->getMessage())->send();
                    }
                }),
        ])
        ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAsientos::route('/'),
            'create' => Pages\CreateAsiento::route('/create'),
            'view'   => Pages\ViewAsiento::route('/{record}'),
        ];
    }
}
