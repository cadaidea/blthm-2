<?php

namespace App\Filament\Pages;
use Filament\Schemas\Schema;

use App\Models\MateriaPrima;
use App\Models\Producto;
use App\Models\ProductoMaterial;
use App\Support\Acl;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class BomProductoBletia extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationLabel = 'Materiales por producto';
    protected static ?string $title = 'Materiales por producto';
    protected static string|\UnitEnum|null $navigationGroup = 'Producción';
    protected string $view = 'filament.pages.bom-producto-bletia';
    protected static ?string $slug = 'materiales-producto';

    public ?array $data = [];

    public static function canAccess(): bool { return in_array(Acl::rol(), ['admin', 'operaciones'], true); }
    public static function shouldRegisterNavigation(): bool { return static::canAccess(); }

    public function mount(): void { $this->form->fill(['producto_id' => null, 'materiales' => []]); }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Producto')->schema([
                Forms\Components\Select::make('producto_id')->label('Producto del catálogo')
                    ->options(fn () => Producto::where('activo', true)->orderBy('nombre')->pluck('nombre', 'id'))
                    ->searchable()->required()->live()
                    ->afterStateUpdated(function ($state, \Filament\Schemas\Components\Utilities\Set $set) {
                        $mats = $state ? ProductoMaterial::where('producto_id', $state)->get()
                            ->map(fn ($m) => ['materia_prima_id' => $m->materia_prima_id, 'cantidad' => (float) $m->cantidad, 'nota' => $m->nota])->all() : [];
                        $set('materiales', $mats);
                    }),
            ]),
            \Filament\Schemas\Components\Section::make('Materiales que consume')->schema([
                Forms\Components\Repeater::make('materiales')->label('')
                    ->addActionLabel('Agregar material')->columns(3)->defaultItems(0)
                    ->schema([
                        Forms\Components\Select::make('materia_prima_id')->label('Material')->required()
                            ->options(fn () => MateriaPrima::where('activo', true)->orderBy('nombre')->pluck('nombre', 'id'))->searchable(),
                        Forms\Components\TextInput::make('cantidad')->label('Cantidad por unidad')->numeric()->required()->minValue(0.001),
                        Forms\Components\TextInput::make('nota')->label('Nota'),
                    ]),
            ]),
        ])->statePath('data');
    }

    public function guardar(): void
    {
        $data = $this->form->getState();
        $pid = $data['producto_id'] ?? null;
        if (! $pid) { Notification::make()->danger()->title('Elige un producto')->send(); return; }
        ProductoMaterial::where('producto_id', $pid)->delete();
        foreach (($data['materiales'] ?? []) as $m) {
            if (empty($m['materia_prima_id'])) continue;
            ProductoMaterial::updateOrCreate(
                ['producto_id' => $pid, 'materia_prima_id' => $m['materia_prima_id']],
                ['cantidad' => (float) ($m['cantidad'] ?? 0), 'nota' => $m['nota'] ?? null]
            );
        }
        Notification::make()->success()->title('Materiales guardados')->body('Se actualizó la lista de materiales del producto.')->send();
    }
}
