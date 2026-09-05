<?php
namespace App\Filament\Resources\ReclamoResource\Pages;

use App\Filament\Resources\ReclamoResource;
use App\Models\Reclamo;
use App\Support\Acl;
use Filament\Actions;
use Illuminate\Support\Str;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class ViewReclamo extends Page
{
    protected static string $resource = ReclamoResource::class;
    protected string $view = 'filament.reclamos.view';
    public $record;
    public array $hist = [];

    public function mount($record): void
    {
        $this->record = Reclamo::with(['cliente', 'pedido'])->findOrFail($record);
        $this->hist = $this->record->pedido_id ? \App\Services\HistorialPedido::de($this->record->pedido_id) : [];
    }

    public function getTitle(): string
    {
        return 'Reclamo ' . ($this->record->folio ?: ('#' . $this->record->id));
    }

    protected function getHeaderActions(): array
    {
        return [
            // avanzar estado
            Actions\Action::make('aRevision')->label('Pasar a revisión')->icon('heroicon-o-magnifying-glass')->color('info')
                ->visible(fn () => Acl::puedeAprobar() && $this->record->estado === 'abierto')
                ->action(fn () => $this->cambiarEstado('en_revision', 'Reclamo en revisión')),

            Actions\Action::make('aReparacion')->label('Pasar a reparación')->icon('heroicon-o-wrench')->color('primary')
                ->visible(fn () => Acl::puedeAprobar() && in_array($this->record->estado, ['abierto', 'en_revision'], true))
                ->action(fn () => $this->cambiarEstado('en_reparacion', 'Reclamo en reparación')),

            // resolver
            Actions\Action::make('resolver')->label('Resolver reclamo')->icon('heroicon-o-check-circle')->color('success')
                ->visible(fn () => Acl::puedeAprobar() && ! in_array($this->record->estado, ['resuelto', 'rechazado'], true))
                ->modalHeading('Resolver reclamo')
                ->form([
                    Forms\Components\Select::make('resolucion')->label('Resolución')->required()
                        ->options([
                            'reparacion' => 'Reparación realizada',
                            'reposicion' => 'Reposición / cambio del producto',
                            'nota_credito' => 'Nota de crédito',
                            'reembolso' => 'Reembolso',
                            'sin_garantia' => 'Sin garantía (rechazado)',
                        ])->live(),
                    Forms\Components\TextInput::make('costo')->label('Costo de la solución (interno)')->numeric()->default(0)
                        ->prefix('$')->helperText('Cuánto costó resolverlo, para control interno.'),
                    Forms\Components\Textarea::make('resolucion_nota')->label('Nota de la resolución')->rows(2),
                ])
                ->action(function (array $data) {
                    $estado = $data['resolucion'] === 'sin_garantia' ? 'rechazado' : 'resuelto';
                    $this->record->update([
                        'resolucion' => $data['resolucion'],
                        'resolucion_nota' => $data['resolucion_nota'] ?? null,
                        'costo' => $data['costo'] ?? 0,
                        'estado' => $estado,
                        'atendido_por' => auth()->id(),
                        'resuelto_at' => now(),
                    ]);
                    if (class_exists(\App\Models\Bitacora::class)) {
                        \App\Models\Bitacora::registrar('resolvió reclamo', 'Reclamo', $this->record->id, ($this->record->folio ?: '') . ' · ' . $data['resolucion']);
                    }
                    Notification::make()->success()->title('Reclamo resuelto')->send();
                    $this->record->refresh();
                }),

            Actions\Action::make('avisarFabricante')->label('Avisar al fabricante')->icon('heroicon-o-paper-airplane')->color('warning')
                ->visible(fn () => \App\Support\Acl::puedeAprobar() && ! in_array($this->record->estado, ['resuelto','rechazado'], true) && ! empty($this->hist()['fabricante_tipo']))
                ->modalHeading('Enviar aviso al fabricante')
                ->modalDescription(fn () => 'Se enviará el PDF del reclamo por correo al ' . (! empty($this->hist()['fabricante_tipo']) && $this->hist()['fabricante_tipo'] === 'proveedor' ? 'proveedor' : 'taller') . '.')
                ->form([
                    \Filament\Forms\Components\Textarea::make('mensaje_extra')->label('Mensaje adicional (opcional)')->rows(2)
                        ->placeholder('Detalle adicional para el fabricante...'),
                ])
                ->action(function (array $data) {
                    $hist = $this->hist();
                    $r = $this->record;
                    $abs = \App\Services\PdfErp::reclamoPdf($r);
                    $tipo = $hist['fabricante_tipo'] ?? null;
                    $dest = [];
                    $destinatario = '—';
                    if ($tipo === 'proveedor' && ! empty($hist['proveedor_email'])) {
                        $email = $hist['proveedor_email'];
                        if ($email) { $dest[] = $email; $destinatario = $hist['proveedor_nombre'] ?: 'Proveedor'; }
                    } else {
                        // taller: usuarios con rol produccion
                        foreach (\Illuminate\Support\Facades\DB::table('users')->where('rol', 'produccion')->where('activo', true)->pluck('email') as $e) $dest[] = $e;
                        $destinatario = 'Taller';
                    }
                    // copia a admin/operaciones
                    foreach (\Illuminate\Support\Facades\DB::table('users')->whereIn('rol', ['admin','operaciones'])->where('activo', true)->pluck('email') as $e) $dest[] = $e;

                    $folio = $r->folio ?: ('#' . $r->id);
                    $extra = $data['mensaje_extra'] ?? null;
                    $resLabel = ['reparacion'=>'Reparación','reposicion'=>'Reposición/cambio','nota_credito'=>'Nota de crédito','reembolso'=>'Reembolso','sin_garantia'=>'Sin garantía'][$r->resolucion ?? ''] ?? null;
                    $cuerpo = '<p>Se ha registrado un <strong>reclamo/garantía</strong> relacionado a un pedido que fabricaste.</p>'
                        . '<p><strong>Reclamo:</strong> ' . $folio . '</p>'
                        . '<p><strong>Producto:</strong> ' . ($r->producto ?: '—') . '</p>'
                        . '<p><strong>Problema:</strong> ' . ($r->tipo_problema ? ucfirst($r->tipo_problema) : '—') . '</p>'
                        . ($r->descripcion ? '<p><strong>Descripción:</strong> ' . nl2br(e($r->descripcion)) . '</p>' : '')
                        . ($extra ? '<p><strong>Nota adicional:</strong> ' . nl2br(e($extra)) . '</p>' : '')
                        . ($resLabel ? '<p><strong>Resolución requerida:</strong> ' . $resLabel . '</p>' : '')
                        . '<p>Se adjunta el expediente completo del reclamo.</p>';
                    $html = \App\Support\CorreoBrand::wrap('Reclamo/Garantía · ' . $folio, $cuerpo);
                    foreach (array_unique(array_filter($dest)) as $to) {
                        try {
                            \Illuminate\Support\Facades\Mail::to($to)->send(new \App\Mail\DocumentoPedido('Reclamo · ' . $folio, $html, [$abs]));
                        } catch (\Throwable $e) { report($e); }
                    }
                    if (class_exists(\App\Models\Bitacora::class)) {
                        \App\Models\Bitacora::registrar('avisó a fabricante', 'Reclamo', $r->id, $folio . ' → ' . $destinatario);
                    }
                    // generar link único para que el fabricante confirme la garantía lista
                    $token = \Illuminate\Support\Str::uuid()->toString();
                    \Illuminate\Support\Facades\DB::table('links_unicos')->insert([
                        'token' => $token, 'tipo' => 'proveedor_garantia',
                        'reclamo_id' => $r->id, 'pedido_id' => $r->pedido_id,
                        'usado' => false, 'intentos' => 0,
                        'expira_en' => now()->addDays(30),
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    $linkUrl = url('/confirmar-garantia/' . $token);
                    // reenviar correo con el link incluido
                    $cuerpoConLink = $cuerpo . '<p><strong>Cuando esté listo, confirma aquí:</strong> <a href="' . $linkUrl . '">' . $linkUrl . '</a></p>';
                    $htmlConLink = \App\Support\CorreoBrand::wrap('Reclamo/Garantía · ' . $folio, $cuerpoConLink);
                    foreach (array_unique(array_filter($dest)) as $to) {
                        try { \Illuminate\Support\Facades\Mail::to($to)->send(new \App\Mail\DocumentoPedido('Reclamo (con link) · ' . $folio, $htmlConLink, [$abs])); } catch (\Throwable $e) { report($e); }
                    }
                    \Filament\Notifications\Notification::make()->success()->title('Aviso enviado a ' . $destinatario)->body('PDF adjunto + link de confirmación incluido.')->send();
                }),

            Actions\Action::make('devolucion')->label('Programar devolución')->icon('heroicon-o-truck')->color('primary')
                ->visible(fn () => \App\Support\Acl::puedeAprobar()
                    && ! in_array($this->record->estado, ['resuelto','rechazado'], true)
                    && ! \Illuminate\Support\Facades\DB::table('despachos')->where('reclamo_id', $this->record->id)->exists())
                ->requiresConfirmation()
                ->modalHeading('Programar devolución al cliente')
                ->modalDescription('Crea un despacho de garantía para devolver el mueble reparado al cliente.')
                ->form([
                    \Filament\Forms\Components\Select::make('ruta')->label('Modalidad')->required()
                        ->options(['transportista' => 'Envío a domicilio', 'retiro_local' => 'Retiro en local'])->default('transportista'),
                    \Filament\Forms\Components\DatePicker::make('fecha_programada')->label('Fecha estimada de devolución'),
                ])
                ->action(function (array $data) {
                    $r = $this->record;
                    $folio = \App\Services\Folios::next('DES');
                    \Illuminate\Support\Facades\DB::table('despachos')->insert([
                        'pedido_id' => $r->pedido_id, 'reclamo_id' => $r->id, 'tipo' => 'garantia',
                        'ruta' => $data['ruta'], 'estado' => 'programado', 'listo' => false,
                        'fecha_programada' => $data['fecha_programada'] ?? null, 'folio' => $folio,
                        'notas' => 'Devolución de garantía · reclamo ' . ($r->folio ?: ('#'.$r->id)),
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    if (class_exists(\App\Models\Bitacora::class)) {
                        \App\Models\Bitacora::registrar('programó devolución garantía', 'Reclamo', $r->id, ($r->folio ?: '') . ' · ' . $folio);
                    }
                    \Filament\Notifications\Notification::make()->success()->title('Devolución programada')->body('Despacho ' . $folio . ' creado.')->send();
                    $this->record->refresh();
                }),

            Actions\Action::make('tallerListo')->label('Taller: reparación lista')->icon('heroicon-o-wrench-screwdriver')->color('success')
                ->visible(fn () => \App\Support\Acl::puedeAprobar()
                    && ($this->hist()['fabricante_tipo'] ?? '') === 'taller'
                    && ! in_array($this->record->estado, ['resuelto','rechazado'], true))
                ->requiresConfirmation()
                ->modalHeading('Confirmar reparación lista (taller)')
                ->modalDescription('El taller confirma que la garantía está reparada y lista para devolver al cliente.')
                ->action(function () {
                    $r = $this->record;
                    $pdfEtq = null;
                    try {
                        $pedido = $r->pedido_id ? \App\Models\PedidoEspecial::find($r->pedido_id) : null;
                        if ($pedido) $pdfEtq = \App\Services\Etiquetas::generarConBultos($pedido, (int) ($r->bultos ?? 1), $r->folio, $r->producto);
                    } catch (\Throwable $e) { report($e); }
                    $folio = $r->folio ?: ('#'.$r->id);
                    if (class_exists(\App\Models\Bitacora::class)) {
                        \App\Models\Bitacora::registrar('taller confirmó garantía lista', 'Reclamo', $r->id, $folio);
                    }
                    \Filament\Notifications\Notification::make()->success()->title('Garantía marcada como lista')->body('Programa el despacho de devolución.')->send();
                    $this->record->refresh();
                    if ($pdfEtq) return response()->download($pdfEtq, 'etiquetas-garantia-' . $folio . '.pdf');
                }),

                        Actions\Action::make('pdf')->label('Descargar PDF')->icon('heroicon-o-arrow-down-tray')->color('gray')
                ->action(function () {
                    $abs = \App\Services\PdfErp::reclamoPdf($this->record);
                    return response()->download($abs, 'reclamo-' . ($this->record->folio ?: $this->record->id) . '.pdf');
                }),
            Actions\Action::make('editar')->label('Editar')->icon('heroicon-o-pencil')->color('gray')
                ->url(fn () => ReclamoResource::getUrl('edit', ['record' => $this->record])),
        ];
    }

    protected function cambiarEstado(string $estado, string $msg): void
    {
        $this->record->update(['estado' => $estado]);
        if (class_exists(\App\Models\Bitacora::class)) {
            \App\Models\Bitacora::registrar('cambió estado de reclamo', 'Reclamo', $this->record->id, ($this->record->folio ?: '') . ' → ' . $estado);
        }
        Notification::make()->success()->title($msg)->send();
        $this->record->refresh();
    }

    protected function hist(): array
    {
        return $this->hist;
    }

    protected function getViewData(): array
    {
        $hist = $this->record->pedido_id ? \App\Services\HistorialPedido::de($this->record->pedido_id) : [];
        return ['r' => $this->record, 'hist' => $hist];
    }
}
