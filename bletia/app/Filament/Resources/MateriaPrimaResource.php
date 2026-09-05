<?php
namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;

use App\Filament\Resources\MateriaPrimaResource\Pages;
use App\Models\MateriaPrima;
use App\Support\Acl;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MateriaPrimaResource extends Resource
{
    protected static ?string $model = MateriaPrima::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube-transparent';
    protected static ?string $navigationLabel = 'Materias primas';
    protected static ?string $modelLabel = 'materia prima';
    protected static ?string $pluralModelLabel = 'materias primas';
    protected static string|\UnitEnum|null $navigationGroup = 'Producción';

    public static function canViewAny(): bool { return Acl::ve(static::class); }
    public static function canCreate(): bool { return Acl::esAdmin() || Acl::esOperaciones() || Acl::rol() === 'bodega'; }
    public static function canEdit($record): bool { return Acl::esAdmin() || Acl::esOperaciones() || Acl::rol() === 'bodega'; }
    public static function canDelete($record): bool { return Acl::puedeEliminar(); }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make()->columns(2)->columnSpanFull()->schema([
                Forms\Components\TextInput::make('nombre')->required()->maxLength(191),
                Forms\Components\Select::make('unidad')->options([
                    'u' => 'Unidad', 'm' => 'Metro', 'm2' => 'Metro²', 'kg' => 'Kilogramo', 'lt' => 'Litro', 'par' => 'Par',
                ])->default('u')->required(),
                Forms\Components\TextInput::make('stock')->numeric()->default(0)->required(),
                Forms\Components\TextInput::make('minimo')->label('Stock mínimo')->numeric()->default(0),
                Forms\Components\TextInput::make('costo')->numeric()->prefix('$')->nullable(),
                Forms\Components\Toggle::make('activo')->default(true),
            ]),
        ]);
    }

public static function getNavigationBadge(): ?string
    {
        $n = \App\Models\MateriaPrima::where('activo', true)->get()->filter(fn ($m) => $m->bajoMinimo())->count();
        return $n > 0 ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('nombre')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('stock')->sortable()
                ->formatStateUsing(fn ($state, $record) => number_format((float) $state, 2) . ' ' . $record->unidad)
                ->color(fn ($record) => $record->bajoMinimo() ? 'danger' : 'success'),
            Tables\Columns\TextColumn::make('minimo')->label('Mínimo')->formatStateUsing(fn ($state, $record) => number_format((float) $state, 2) . ' ' . $record->unidad),
            Tables\Columns\TextColumn::make('costo')->money('usd')->placeholder('—'),
            Tables\Columns\IconColumn::make('activo')->boolean(),
        ])
        ->actions([
            Actions\Action::make('entrada')->label('Ingresar stock')->icon('heroicon-o-plus-circle')->color('success')
                ->visible(fn () => Acl::esAdmin() || Acl::esOperaciones() || Acl::rol() === 'bodega')
                ->form([
                    Forms\Components\TextInput::make('cantidad')->numeric()->required()->minValue(0.01),
                    Forms\Components\TextInput::make('nota')->label('Nota'),
                ])
                ->action(function (MateriaPrima $record, array $data) {
                    \App\Models\MovimientoMaterial::create([
                        'materia_prima_id' => $record->id, 'tipo' => 'entrada',
                        'cantidad' => $data['cantidad'], 'nota' => $data['nota'] ?? null, 'user_id' => auth()->id(),
                    ]);
                    \Filament\Notifications\Notification::make()->success()->title('Stock ingresado')->send();
                }),
            Actions\EditAction::make()->visible(fn () => Acl::esAdmin() || Acl::esOperaciones() || Acl::rol() === 'bodega'),
        ])
        ->defaultSort('nombre');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListMateriaPrimas::route('/'),
            'create' => Pages\CreateMateriaPrima::route('/create'),
            'edit'   => Pages\EditMateriaPrima::route('/{record}/edit'),
        ];
    }
}
