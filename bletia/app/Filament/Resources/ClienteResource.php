<?php
namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;


use App\Support\Acl;
use App\Filament\Resources\ClienteResource\Pages;
use App\Models\Cliente;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ClienteResource extends Resource
{
    protected static ?string $model = Cliente::class;


    public static function canViewAny(): bool
    {
        return Acl::ve(static::class);
    }
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return \App\Models\Cliente::query();
    }
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $modelLabel = 'Cliente';
    protected static ?string $pluralModelLabel = 'Clientes';
    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Datos')->columns(2)->columnSpanFull()->schema([
                Forms\Components\TextInput::make('nombre')->required()->columnSpanFull(),
                Forms\Components\Select::make('tipo_id')->label('Tipo de identificación')
                    ->options(['cedula' => 'Cédula', 'ruc' => 'RUC', 'pasaporte' => 'Pasaporte'])
                    ->native(false),
                Forms\Components\TextInput::make('identificacion')->label('N° de identificación'),
                Forms\Components\TextInput::make('email')->email(),
                Forms\Components\TextInput::make('telefono')->label('Teléfono')->tel(),
                Forms\Components\TextInput::make('celular')->tel(),
                Forms\Components\TextInput::make('ciudad'),
                Forms\Components\TextInput::make('provincia'),
                Forms\Components\TextInput::make('direccion')->label('Dirección')->columnSpanFull(),
                Forms\Components\Textarea::make('notas')->rows(2)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('nombre')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('identificacion')->label('Identificación')->searchable()->placeholder('—'),
            Tables\Columns\TextColumn::make('email')->searchable()->placeholder('—'),
            Tables\Columns\TextColumn::make('telefono')->placeholder('—'),
            Tables\Columns\TextColumn::make('ciudad')->placeholder('—'),
            Tables\Columns\TextColumn::make('created_at')->date('d/m/Y')->label('Alta')->sortable(),
        ])->defaultSort('id', 'desc')
        ->actions([Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCliente::route('/'),
            'edit'  => Pages\EditCliente::route('/{record}/edit'),
        ];
    }
}
