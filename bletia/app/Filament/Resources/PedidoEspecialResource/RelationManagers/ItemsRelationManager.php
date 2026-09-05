<?php
namespace App\Filament\Resources\PedidoEspecialResource\RelationManagers;
use Filament\Actions;
use Filament\Schemas\Schema;

use App\Models\Producto;
use App\Models\Proveedor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
    protected static ?string $title = 'Ítems y especificaciones';

    /** Campo (texto) + su foto, lado a lado. */
    protected static function campoConFoto(string $name, string $label, string $fotoName): array
    {
        return [
            Forms\Components\TextInput::make($name)->label($label),
            Forms\Components\FileUpload::make($fotoName)->label('Foto ' . strtolower($label))->image()->directory('pedido-local')->imageEditor(),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Producto / modelo')->columns(2)->schema([
                Forms\Components\Select::make('producto_id')->label('Producto cargado')
                    ->options(fn () => Producto::where('activo', true)->orderBy('nombre')->pluck('nombre', 'id'))
                    ->searchable()->preload()
                    ->helperText('Vacío = modelo especial (escribe el nombre y sube su foto).'),
                Forms\Components\TextInput::make('nombre')->label('Nombre / modelo'),
                Forms\Components\FileUpload::make('foto_modelo')->label('Foto del modelo (especial)')->image()->directory('pedido-local')->imageEditor(),
                Forms\Components\Select::make('proveedor_id')->label('Proveedor')
                    ->options(fn () => Proveedor::where('activo', true)->pluck('nombre', 'id'))->searchable(),
                Forms\Components\TextInput::make('cantidad')->numeric()->default(1),
                Forms\Components\TextInput::make('precio')->label('PVP (IVA incl.)')->numeric()->prefix('$'),
                Forms\Components\TextInput::make('bultos')->label('Bultos (paquetes)')->numeric()->default(1)->minValue(0)
                    ->helperText('Cuántos paquetes/cajas ocupa este producto al despachar.'),
            ]),
            \Filament\Schemas\Components\Section::make('Especificaciones (todas opcionales)')->columns(2)->schema([
                ...self::campoConFoto('tapiz_principal', 'Tapiz principal', 'foto_tapiz_principal'),
                ...self::campoConFoto('tapiz_secundario', 'Tapiz secundario', 'foto_tapiz_secundario'),
                ...self::campoConFoto('cojines', 'Cojines principal', 'foto_cojines'),
                ...self::campoConFoto('cojines_secundario', 'Cojines secundario', 'foto_cojines_secundario'),
                ...self::campoConFoto('lacado', 'Lacado', 'foto_lacado'),
            ]),
            Forms\Components\Textarea::make('notas_adicionales')->label('Notas (internas / proveedor)')->rows(2)->columnSpanFull(),
            Forms\Components\FileUpload::make('fotos_ref')->label('Fotos de referencia extra (máx. 6)')
                ->image()->multiple()->maxFiles(6)->directory('pedido-local')->reorderable()->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->label('#'),
            Tables\Columns\TextColumn::make('nombre')->label('Modelo')->placeholder('—'),
            Tables\Columns\TextColumn::make('cantidad'),
            Tables\Columns\TextColumn::make('proveedor_id')->label('Prov.')->placeholder('—'),
            Tables\Columns\TextColumn::make('tapiz_principal')->label('Tapiz')->placeholder('—'),
            Tables\Columns\TextColumn::make('lacado')->placeholder('—'),
        ])->headerActions([
            Actions\CreateAction::make()->label('Agregar ítem'),
        ])->actions([
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ]);
    }
}
