<?php

namespace App\Filament\Pages;
use Filament\Schemas\Schema;


use App\Support\Acl;
use App\Models\Ajuste;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class DatosEmpresaErp extends Page implements HasForms
{

    public static function canAccess(): bool { return \App\Support\Acl::esAdmin(); }

    public static function canViewAny(): bool
    {
        return Acl::esAdmin();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';
    protected static string|\UnitEnum|null $navigationGroup = 'Ajustes';
    protected static ?string $title = 'Datos de empresa';
    protected static ?string $navigationLabel = 'Datos de empresa';
    protected static ?int $navigationSort = 9;
    protected string $view = 'filament.pages.datos-empresa-erp';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'erp_ruc'       => Ajuste::get('erp_ruc'),
            'erp_direccion' => Ajuste::get('erp_direccion'),
            'erp_telefono'  => Ajuste::get('erp_telefono'),
            'erp_ciudad'    => Ajuste::get('erp_ciudad') ?: 'Cuenca',
            'erp_email'     => Ajuste::get('erp_email'),
            'erp_logo_pdf'  => Ajuste::get('erp_logo_pdf'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Datos para documentos (PDF)')
                ->description('Aparecen en guías, órdenes y documentos del ERP.')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('erp_ruc')->label('RUC / Cédula'),
                    Forms\Components\TextInput::make('erp_telefono')->label('Teléfono')->tel(),
                    Forms\Components\TextInput::make('erp_direccion')->label('Dirección')->columnSpanFull(),
                    Forms\Components\TextInput::make('erp_ciudad')->label('Ciudad'),
                    Forms\Components\TextInput::make('erp_email')->label('Email')->email(),
                ]),
            \Filament\Schemas\Components\Section::make('Logo del documento')
                ->description('Logo que se imprime en los PDF. Usa PNG (preferible fondo transparente, versión oscura).')
                ->schema([
                    Forms\Components\FileUpload::make('erp_logo_pdf')
                        ->label('Logo para PDF (PNG)')
                        ->image()
                        ->acceptedFileTypes(['image/png'])
                        ->maxSize(2048)
                        ->disk('public')
                        ->directory('marca')
                        ->visibility('public')
                        ->imagePreviewHeight('80'),
                ]),
        ])->statePath('data');
    }

    public function save(): void
    {
        foreach ($this->form->getState() as $k => $v) {
            Ajuste::set($k, is_array($v) ? (collect($v)->filter()->first() ?: '') : $v);
        }
        Notification::make()->success()->title('Datos guardados')->send();
    }
}
