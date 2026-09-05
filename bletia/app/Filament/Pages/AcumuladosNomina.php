<?php

namespace App\Filament\Pages;

use App\Models\Empleado;
use App\Models\PagoBeneficio;
use App\Services\AcumuladosBeneficios;
use App\Services\PagosBeneficio;
use App\Support\Acl;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class AcumuladosNomina extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wallet';
    protected static string|\UnitEnum|null $navigationGroup = 'Nómina / RRHH';
    protected static ?string $title = 'Acumulados de beneficios';
    protected static ?string $navigationLabel = 'Acumulados';
    protected static ?int $navigationSort = 4;
    protected string $view = 'filament.pages.acumulados-nomina';

    public array $filas = [];

    public static function canAccess(): bool { return \App\Support\Acl::esAdmin(); }

    public static function canViewAny(): bool { return Acl::esAdmin(); }
    public static function shouldRegisterNavigation(): bool { return static::canViewAny(); }

    public function mount(): void { $this->cargar(); }

    public function cargar(): void { $this->filas = AcumuladosBeneficios::todos(); }

    /** Acción para pagar un beneficio de un empleado. */
    public function pagarBeneficioAction(): Action
    {
        return Action::make('pagarBeneficio')
            ->label('Pagar beneficio')
            ->icon('heroicon-o-banknotes')
            ->form([
                Forms\Components\Select::make('empleado_id')->label('Empleado')->required()
                    ->options(fn () => Empleado::where('activo', true)->where('relacion', 'dependencia')->pluck('nombre', 'id')),
                Forms\Components\Select::make('tipo')->label('Beneficio')->required()->options(PagoBeneficio::TIPOS),
                Forms\Components\TextInput::make('monto')->numeric()->prefix('$')->required(),
                Forms\Components\DatePicker::make('fecha')->required()->default(now()),
                Forms\Components\TextInput::make('periodo')->label('Periodo (ej. 2026)'),
                Forms\Components\Select::make('metodo_pago')->options(['transferencia' => 'Transferencia', 'efectivo' => 'Efectivo', 'cheque' => 'Cheque'])->default('transferencia'),
                Forms\Components\TextInput::make('nro_comprobante')->label('N° comprobante'),
                Forms\Components\FileUpload::make('adjunto')->label('Comprobante')->directory('beneficios')->disk('public'),
                Forms\Components\Textarea::make('detalle')->rows(2),
            ])
            ->action(function (array $data) {
                $pago = PagoBeneficio::create($data + ['estado' => 'pagado', 'creado_por' => auth()->id()]);
                PagosBeneficio::asentar($pago->fresh('empleado'));
                \App\Services\PdfNomina::beneficio($pago->fresh('empleado'));
                $this->cargar();
                Notification::make()->success()->title('Beneficio pagado y contabilizado')->body('Recibo generado. Descárgalo desde el historial de pagos.')->send();
            });
    }

    protected function getHeaderActions(): array
    {
        return [$this->pagarBeneficioAction()];
    }
}
