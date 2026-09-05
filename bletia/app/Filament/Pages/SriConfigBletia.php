<?php

namespace App\Filament\Pages;
use Filament\Schemas\Schema;

use App\Models\Ajuste;
use App\Models\SriEstablecimiento;
use App\Models\SriPuntoEmision;
use App\Services\Sri\Emisor;
use App\Support\Acl;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;

class SriConfigBletia extends Page implements HasForms, \Filament\Actions\Contracts\HasActions
{
    use InteractsWithForms;
    use \Filament\Actions\Concerns\InteractsWithActions;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationLabel = 'Facturación SRI';
    protected static ?string $title = 'Configuración de facturación electrónica';
    protected static string|\UnitEnum|null $navigationGroup = 'Ajustes';
    protected static ?int $navigationSort = 8;
    protected string $view = 'filament.pages.sri-config-bletia';

    public ?array $data = [];

    public static function canAccess(): bool { return Acl::esAdmin(); }
    public static function shouldRegisterNavigation(): bool { return static::canAccess(); }

    public function mount(): void
    {
        $keys = ['emisor_ruc', 'emisor_razon', 'emisor_nombre_comercial', 'emisor_dir_matriz', 'emisor_dir_estab',
            'emisor_obligado_contabilidad', 'emisor_contribuyente_especial', 'emisor_agente_retencion', 'emisor_regimen_micro',
            'emisor_estab', 'emisor_pto_emi', 'sri_ambiente', 'sri_p12_path', 'sri_p12_pass'];
        $vals = [];
        foreach ($keys as $k) $vals[$k] = Ajuste::get($k);
        $vals['emisor_obligado_contabilidad'] = $vals['emisor_obligado_contabilidad'] ?: 'NO';
        $vals['emisor_regimen_micro'] = $vals['emisor_regimen_micro'] ?: 'NO';
        $vals['sri_ambiente'] = $vals['sri_ambiente'] ?: '1';
        $vals['emisor_estab'] = $vals['emisor_estab'] ?: '001';
        $vals['emisor_pto_emi'] = $vals['emisor_pto_emi'] ?: '001';
        $vals['p12_subida'] = null;

        // cargar establecimientos y puntos
        $vals['establecimientos'] = SriEstablecimiento::with('puntos')->orderBy('codigo')->get()->map(fn ($e) => [
            'codigo' => $e->codigo, 'nombre' => $e->nombre, 'direccion' => $e->direccion, 'activo' => $e->activo,
            'puntos' => $e->puntos->map(fn ($p) => ['codigo' => $p->codigo, 'nombre' => $p->nombre, 'activo' => $p->activo])->toArray(),
        ])->toArray();

        $this->form->fill($vals);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Datos del emisor')->columns(2)->schema([
                Forms\Components\TextInput::make('emisor_ruc')->label('RUC')->required()->maxLength(13),
                Forms\Components\TextInput::make('emisor_razon')->label('Razón social')->required(),
                Forms\Components\TextInput::make('emisor_nombre_comercial')->label('Nombre comercial'),
                Forms\Components\Select::make('emisor_obligado_contabilidad')->label('Obligado a contabilidad')->options(['SI' => 'Sí', 'NO' => 'No'])->default('NO'),
                Forms\Components\TextInput::make('emisor_dir_matriz')->label('Dirección matriz')->required()->columnSpanFull(),
                Forms\Components\TextInput::make('emisor_contribuyente_especial')->label('Contribuyente especial (Nro resolución)'),
                Forms\Components\TextInput::make('emisor_agente_retencion')->label('Agente de retención (Nro resolución)'),
                Forms\Components\Select::make('emisor_regimen_micro')->label('Régimen RIMPE')->options(['SI' => 'Sí', 'NO' => 'No'])->default('NO'),
            ]),

            \Filament\Schemas\Components\Section::make('Firma electrónica (.p12)')->columns(2)
                ->description('Sube tu certificado de firma desde aquí. No necesitas FTP ni SSH.')
                ->schema([
                    Forms\Components\FileUpload::make('p12_subida')->label('Subir archivo .p12')
                        ->disk('local')->directory('sri')->visibility('private')
                        ->acceptedFileTypes(['application/x-pkcs12', 'application/pkcs12', 'application/octet-stream'])
                        ->helperText('Selecciona tu archivo .p12 del Banco Central / Security Data / Uanataca.')
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('sri_p12_pass')->label('Clave del .p12')->password()->revealable()->required(),
                    Forms\Components\Select::make('sri_ambiente')->label('Ambiente')->options(['1' => 'Pruebas', '2' => 'Producción'])->default('1')->required(),
                    Forms\Components\Placeholder::make('estado_firma')->label('Estado de la firma')
                        ->content(fn () => $this->estadoFirma())->columnSpanFull(),
                ]),

            \Filament\Schemas\Components\Section::make('Establecimientos y puntos de emisión')
                ->description('Define tus locales y sus puntos de emisión. Cada combinación tiene su propia secuencia de facturas (lo exige el SRI).')
                ->schema([
                    Forms\Components\Repeater::make('establecimientos')->label('')->schema([
                        \Filament\Schemas\Components\Grid::make(4)->schema([
                            Forms\Components\TextInput::make('codigo')->label('Código')->placeholder('001')->required()->maxLength(3)
                                ->mask('999')->columnSpan(1),
                            Forms\Components\TextInput::make('nombre')->label('Nombre')->placeholder('Matriz')->columnSpan(1),
                            Forms\Components\TextInput::make('direccion')->label('Dirección')->required()->columnSpan(2),
                        ]),
                        Forms\Components\Toggle::make('activo')->label('Activo')->default(true)->inline(),
                        Forms\Components\Repeater::make('puntos')->label('Puntos de emisión')->schema([
                            \Filament\Schemas\Components\Grid::make(3)->schema([
                                Forms\Components\TextInput::make('codigo')->label('Código')->placeholder('001')->required()->maxLength(3)->mask('999'),
                                Forms\Components\TextInput::make('nombre')->label('Nombre')->placeholder('Caja 1 / Online'),
                                Forms\Components\Toggle::make('activo')->label('Activo')->default(true)->inline(),
                            ]),
                        ])->defaultItems(1)->addActionLabel('Agregar punto de emisión')->collapsible()
                            ->itemLabel(fn (array $state): ?string => isset($state['codigo']) ? 'Punto ' . $state['codigo'] : null),
                    ])->defaultItems(0)->addActionLabel('Agregar establecimiento')->collapsible()
                        ->itemLabel(fn (array $state): ?string => isset($state['codigo']) ? ('Estab. ' . $state['codigo'] . ($state['nombre'] ? ' · ' . $state['nombre'] : '')) : null),
                ]),

            \Filament\Schemas\Components\Section::make('Numeración de comprobantes (secuencial)')
                ->description('Usa el botón "Fijar secuencial" (arriba, junto al título) para continuar la numeración si ya facturabas con otro sistema.')
                ->schema([
                    Forms\Components\Placeholder::make('sec_historial')->label('')->columnSpanFull()
                        ->content(fn () => $this->estadoSecuenciales()),
                ]),
        ])->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('fijarSecuencial')
                ->label('Fijar secuencial')->icon('heroicon-o-hashtag')->color('warning')
                ->modalHeading('Continuar numeración')
                ->modalDescription('Escribe el número de tu ÚLTIMO comprobante emitido (ej: si tu última factura fue 001-001-000000001, la siguiente que emita este sistema será la 000000002).')
                ->form([
                    Forms\Components\Select::make('tipo')->label('Tipo de documento')->native(false)->required()
                        ->options(['01' => 'Factura', '04' => 'Nota de crédito', '06' => 'Guía de remisión'])->default('01'),
                    Forms\Components\TextInput::make('estab')->label('Establecimiento')->required()
                        ->default(fn () => Emisor::estab())->maxLength(3),
                    Forms\Components\TextInput::make('ptoemi')->label('Punto de emisión')->required()
                        ->default(fn () => Emisor::ptoEmi())->maxLength(3),
                    Forms\Components\TextInput::make('ultimo')->label('N° de tu último comprobante emitido')->numeric()->minValue(0)->required()
                        ->helperText('Ej: 1 (para 001-001-000000001)'),
                ])
                ->action(function (array $data) {
                    $tipo = $data['tipo'];
                    $estab = str_pad((string) $data['estab'], 3, '0', STR_PAD_LEFT);
                    $ptoEmi = str_pad((string) $data['ptoemi'], 3, '0', STR_PAD_LEFT);
                    $ultimo = (int) $data['ultimo'];

                    \App\Services\Sri\Secuencial::fijar($tipo, $ultimo + 1, $estab, $ptoEmi);

                    $siguiente = str_pad((string) ($ultimo + 1), 9, '0', STR_PAD_LEFT);
                    Notification::make()->success()->title('Secuencial actualizado')
                        ->body("Tu próximo comprobante ({$tipo}) será {$estab}-{$ptoEmi}-{$siguiente}.")->send();
                    $this->redirect(static::getUrl());
                }),
        ];
    }

    public function estadoSecuenciales(): \Illuminate\Support\HtmlString
    {
        $etiquetas = ['01' => 'Factura', '04' => 'Nota de crédito', '06' => 'Guía de remisión'];
        $filas = \Illuminate\Support\Facades\DB::table('sri_secuenciales')->orderBy('cod_doc')->get();
        if ($filas->isEmpty()) {
            return new \Illuminate\Support\HtmlString('<p style="color:#888;font-size:.85rem">Aún no se ha fijado ni emitido ningún comprobante.</p>');
        }
        $html = '<table style="width:100%;font-size:.85rem;border-collapse:collapse;margin-top:8px">';
        $html .= '<tr style="text-align:left;color:#888;border-bottom:1px solid #eee">
            <th style="padding:6px 8px">Documento</th><th style="padding:6px 8px">Establ.</th>
            <th style="padding:6px 8px">Pto. emisión</th><th style="padding:6px 8px">Último guardado</th>
            <th style="padding:6px 8px">Próximo a emitir</th></tr>';
        foreach ($filas as $f) {
            $ultimo = (int) $f->ultimo;
            $siguiente = str_pad((string) ($ultimo + 1), 9, '0', STR_PAD_LEFT);
            $html .= '<tr style="border-bottom:1px solid #f5f5f5">'
                . '<td style="padding:6px 8px">' . ($etiquetas[$f->cod_doc] ?? $f->cod_doc) . '</td>'
                . '<td style="padding:6px 8px">' . e($f->estab) . '</td>'
                . '<td style="padding:6px 8px">' . e($f->pto_emi) . '</td>'
                . '<td style="padding:6px 8px">' . str_pad((string) $ultimo, 9, '0', STR_PAD_LEFT) . '</td>'
                . '<td style="padding:6px 8px;font-weight:600">' . $f->estab . '-' . $f->pto_emi . '-' . $siguiente . '</td>'
                . '</tr>';
        }
        $html .= '</table>';
        return new \Illuminate\Support\HtmlString($html);
    }

    protected function estadoFirma(): string
    {
        $path = Emisor::p12Path();
        if (! is_file($path)) return '⚠ No hay firma cargada todavía.';
        $pass = Emisor::p12Pass();
        if (! $pass) return 'ℹ Firma cargada. Falta guardar la clave y probar.';
        try {
            $certs = [];
            if (! openssl_pkcs12_read(file_get_contents($path), $certs, $pass)) return '✗ La clave no abre el archivo .p12.';
            $info = openssl_x509_parse($certs['cert']);
            $cn = $info['subject']['CN'] ?? '—';
            $vence = isset($info['validTo_time_t']) ? date('d/m/Y', $info['validTo_time_t']) : '—';
            $usage = $info['extensions']['keyUsage'] ?? '';
            $esBce = isset($info['extensions']) && collect(array_keys($info['extensions']))->contains(fn ($k) => str_starts_with($k, '1.3.6.1.4.1.37947'));
            $okFirma = str_contains($usage, 'Digital Signature') || $esBce;
            return ($okFirma ? '✓' : '⚠') . ' ' . $cn . ' · vence ' . $vence . ($okFirma ? '' : ' · (no es certificado de firma)');
        } catch (\Throwable $e) {
            return '✗ ' . $e->getMessage();
        }
    }

    public function guardar(): void
    {
        $data = $this->form->getState();
        // los campos "sec_*" son solo para el boton "Fijar secuencial", no son Ajustes normales.
        foreach (['sec_tipo', 'sec_estab', 'sec_ptoemi', 'sec_ultimo'] as $k) unset($data[$k]);

        // 1) si subió un .p12, reconvertirlo automáticamente (Node) a uno que PHP pueda abrir
        if (! empty($data['p12_subida'])) {
            $sub = is_array($data['p12_subida']) ? reset($data['p12_subida']) : $data['p12_subida'];
            if ($sub) {
                $origen = Storage::disk('local')->path($sub);
                $pass = (string) ($data['sri_p12_pass'] ?? Ajuste::get('sri_p12_pass') ?? '');
                $destino = storage_path('app/sri/firma.p12');
                @mkdir(dirname($destino), 0775, true);

                // intentar reconversión vía microservicio Node (maneja el cifrado legacy del BCE)
                $prep = \App\Services\Sri\PrepararFirma::preparar($origen, $pass, $destino);
                if ($prep['ok']) {
                    Ajuste::set('sri_p12_path', $destino);
                    // borrar el archivo subido crudo (ya tenemos el limpio)
                    try { Storage::disk('local')->delete($sub); } catch (\Throwable $e) {}
                    Notification::make()->success()->title('Firma preparada')->body('El certificado se procesó y quedó listo para firmar.')->send();
                } else {
                    // si falla la reconversión, usar el archivo tal cual y avisar
                    Ajuste::set('sri_p12_path', $origen);
                    Notification::make()->warning()->title('Firma subida sin reconvertir')
                        ->body('No se pudo preparar automáticamente: ' . $prep['msg'] . '. Se intentará usar tal cual.')->persistent()->send();
                }
            }
        }

        // 2) guardar ajustes del emisor
        $claves = ['emisor_ruc', 'emisor_razon', 'emisor_nombre_comercial', 'emisor_dir_matriz',
            'emisor_obligado_contabilidad', 'emisor_contribuyente_especial', 'emisor_agente_retencion',
            'emisor_regimen_micro', 'sri_ambiente', 'sri_p12_pass'];
        foreach ($claves as $k) {
            if (array_key_exists($k, $data)) Ajuste::set($k, (string) ($data[$k] ?? ''));
        }

        // 3) sincronizar establecimientos y puntos
        $this->sincronizarEstablecimientos($data['establecimientos'] ?? []);

        // 4) mantener estab/pto_emi por defecto = primer establecimiento/punto activo
        $primerEstab = collect($data['establecimientos'] ?? [])->firstWhere('activo', true) ?? collect($data['establecimientos'] ?? [])->first();
        if ($primerEstab) {
            Ajuste::set('emisor_estab', str_pad($primerEstab['codigo'] ?? '001', 3, '0', STR_PAD_LEFT));
            Ajuste::set('emisor_dir_estab', $primerEstab['direccion'] ?? Emisor::dirMatriz());
            $primerPunto = collect($primerEstab['puntos'] ?? [])->firstWhere('activo', true) ?? collect($primerEstab['puntos'] ?? [])->first();
            if ($primerPunto) Ajuste::set('emisor_pto_emi', str_pad($primerPunto['codigo'] ?? '001', 3, '0', STR_PAD_LEFT));
        }

        Notification::make()->success()->title('Configuración guardada')->send();
    }

    protected function sincronizarEstablecimientos(array $estabs): void
    {
        $codigosVistos = [];
        foreach ($estabs as $e) {
            if (empty($e['codigo'])) continue;
            $cod = str_pad($e['codigo'], 3, '0', STR_PAD_LEFT);
            $codigosVistos[] = $cod;
            $estab = SriEstablecimiento::updateOrCreate(
                ['codigo' => $cod],
                ['nombre' => $e['nombre'] ?? null, 'direccion' => $e['direccion'] ?? '', 'activo' => (bool) ($e['activo'] ?? true)]
            );
            $puntosVistos = [];
            foreach (($e['puntos'] ?? []) as $p) {
                if (empty($p['codigo'])) continue;
                $pcod = str_pad($p['codigo'], 3, '0', STR_PAD_LEFT);
                $puntosVistos[] = $pcod;
                SriPuntoEmision::updateOrCreate(
                    ['establecimiento_id' => $estab->id, 'codigo' => $pcod],
                    ['nombre' => $p['nombre'] ?? null, 'activo' => (bool) ($p['activo'] ?? true)]
                );
            }
            // borrar puntos quitados de la UI
            $estab->puntos()->whereNotIn('codigo', $puntosVistos ?: ['__none__'])->delete();
        }
        // borrar establecimientos quitados de la UI
        SriEstablecimiento::whereNotIn('codigo', $codigosVistos ?: ['__none__'])->delete();
    }

    public function probar(): void
    {
        $chk = Emisor::completo();
        if (! $chk['ok']) { Notification::make()->danger()->title('Faltan datos')->body(implode(', ', $chk['faltan']))->send(); return; }
        try {
            $certs = [];
            if (! openssl_pkcs12_read(file_get_contents(Emisor::p12Path()), $certs, Emisor::p12Pass())) {
                Notification::make()->danger()->title('No se pudo abrir el .p12')->body('Clave incorrecta o archivo dañado.')->send(); return;
            }
            $info = openssl_x509_parse($certs['cert']);
            $cn = $info['subject']['CN'] ?? '—';
            $vence = isset($info['validTo_time_t']) ? date('d/m/Y', $info['validTo_time_t']) : '—';
            $usage = $info['extensions']['keyUsage'] ?? '';
            $esBce = isset($info['extensions']) && collect(array_keys($info['extensions']))->contains(fn ($k) => str_starts_with($k, '1.3.6.1.4.1.37947'));
            $okFirma = str_contains($usage, 'Digital Signature') || $esBce;
            if (! $okFirma) {
                Notification::make()->warning()->title('Certificado cargado, pero…')->body($cn . ' · vence ' . $vence . '. No es del tipo Firma Digital; revisa el archivo.')->persistent()->send();
                return;
            }
            Notification::make()->success()->title('Firma válida ✓')->body('Certificado: ' . $cn . ' · vence ' . $vence)->persistent()->send();
        } catch (\Throwable $e) {
            Notification::make()->danger()->title('Error')->body($e->getMessage())->send();
        }
    }
}
