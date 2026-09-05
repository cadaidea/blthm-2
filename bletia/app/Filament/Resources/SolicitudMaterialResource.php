<?php
namespace App\Filament\Resources;
use Filament\Actions;

use App\Filament\Resources\SolicitudMaterialResource\Pages;
use App\Models\MovimientoMaterial;
use App\Models\MateriaPrima;
use App\Support\Acl;
use App\Support\CorreoBrand;
use App\Mail\DocumentoPedido;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SolicitudMaterialResource extends Resource
{
    protected static ?string $model = MovimientoMaterial::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox-arrow-down';
    protected static ?string $navigationLabel = 'Solicitudes de material';
    protected static ?string $modelLabel = 'solicitud de material';
    protected static ?string $pluralModelLabel = 'solicitudes de material';
    protected static string|\UnitEnum|null $navigationGroup = 'Producción';

    public static function canViewAny(): bool { return Acl::ve(static::class); }
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['materia'])->where('tipo', 'solicitud');
    }

    public static function getNavigationBadge(): ?string
    {
        $n = MovimientoMaterial::where('tipo', 'solicitud')->where('estado', 'solicitado')->count();
        return $n ? (string) $n : null;
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
            Tables\Columns\TextColumn::make('materia.nombre')->label('Material')->searchable()
                ->formatStateUsing(fn ($state, $record) => $state ?? optional(MateriaPrima::find($record->materia_prima_id))->nombre ?? '—'),
            Tables\Columns\TextColumn::make('cantidad')->label('Cantidad')
                ->formatStateUsing(fn ($state, $record) => number_format((float) $state, 2) . ' ' . (optional(MateriaPrima::find($record->materia_prima_id))->unidad ?? '')),
            Tables\Columns\TextColumn::make('pedido_id')->label('Pedido')
                ->formatStateUsing(fn ($state) => $state ? (DB::table('pedidos')->where('id', $state)->value('folio') ?: ('#' . $state)) : '—'),
            Tables\Columns\TextColumn::make('user_nombre')->label('Pedido por')
                ->state(fn ($record) => DB::table('users')->where('id', $record->user_id)->value('name') ?? '—'),
            Tables\Columns\TextColumn::make('estado')->label('Estado')->badge()
                ->formatStateUsing(fn ($state) => $state === 'entregado' ? 'Entregado' : 'Solicitado')
                ->color(fn ($state) => $state === 'entregado' ? 'ok' : 'warning'),
            Tables\Columns\TextColumn::make('nota')->label('Nota')->placeholder('—')->wrap()->limit(50),
        ])
        ->filters([
            Tables\Filters\SelectFilter::make('estado')->label('Estado')
                ->options(['solicitado' => 'Solicitado', 'entregado' => 'Entregado']),
        ])
        ->actions([
            Actions\Action::make('entregar_firma')->label('Entregar con firma')->icon('heroicon-o-pencil-square')->color('success')
                ->visible(fn (MovimientoMaterial $r) => $r->estado === 'solicitado' && (Acl::esAdmin() || Acl::esOperaciones() || Acl::rol() === 'bodega'))
                ->modalHeading('Entrega de material con firma')
                ->modalWidth('lg')
                ->form([
                    \Filament\Forms\Components\Placeholder::make('contexto_entrega')->label('Detalle de la solicitud')
                        ->content(function (MovimientoMaterial $record) {
                            $mp = MateriaPrima::find($record->materia_prima_id);
                            $ped = $record->pedido_id ? DB::table('pedidos')->where('id', $record->pedido_id)->first() : null;
                            $cli = $ped && $ped->cliente_id ? DB::table('clientes')->where('id', $ped->cliente_id)->value('nombre') : null;
                            $items = $ped ? DB::table('pedido_items')->where('pedido_id', $ped->id)->pluck('nombre')->filter()->implode(', ') : '';
                            $txt = 'Material: ' . ($mp->nombre ?? '-') . ' · ' . number_format((float) $record->cantidad, 2) . ' ' . ($mp->unidad ?? '');
                            if ($ped) $txt .= ' | Pedido: ' . ($ped->folio ?: ('#' . $ped->id));
                            if ($items) $txt .= ' | Para fabricar: ' . $items;
                            if ($cli) $txt .= ' | Cliente: ' . $cli;
                            return $txt;
                        }),
                    \Filament\Forms\Components\TextInput::make('recibido_nombre')->label('Nombre de quien recibe')->required(),
                    \Filament\Forms\Components\TextInput::make('recibido_cedula')->label('Cédula de quien recibe'),
                    \Filament\Forms\Components\ViewField::make('firma')->label('Firma de quien recibe')
                        ->view('filament.forms.firma-pad'),
                ])
                ->action(function (MovimientoMaterial $record, array $data) {
                    $mp = MateriaPrima::find($record->materia_prima_id);
                    if (! $mp) return;
                    // V_ENTREGA_GUARD: no entregar si no hay stock suficiente
                    $req = (float) $record->cantidad;
                    $disp = (float) $mp->stock;
                    if ($disp < $req) {
                        $ped = $record->pedido_id ? (object) ['id' => $record->pedido_id, 'folio' => DB::table('pedidos')->where('id', $record->pedido_id)->value('folio')] : (object) ['id' => 0, 'folio' => null];
                        \App\Services\Materiales::alarmaFaltante($ped, [['materia' => $mp->nombre, 'unidad' => $mp->unidad, 'requiere' => $req, 'disponible' => $disp, 'falta' => round($req - $disp, 3)]], 'entrega de material en bodega');
                        \Filament\Notifications\Notification::make()->danger()->title('No hay stock suficiente')->body('Solo hay ' . number_format($disp, 2) . ' ' . $mp->unidad . ' de ' . $mp->nombre . ' y se piden ' . number_format($req, 2) . '. La solicitud queda abierta y se avisó a Operaciones y Dueño.')->persistent()->send();
                        return;
                    }
                    // descontar stock
                    $mp->stock = max(0, $disp - $req);
                    $mp->save();
                    // guardar datos de entrega + firma
                    $record->update([
                        'tipo' => 'entrega', 'estado' => 'entregado',
                        'recibido_nombre' => $data['recibido_nombre'] ?? null,
                        'recibido_cedula' => $data['recibido_cedula'] ?? null,
                        'firma' => $data['firma'] ?? null,
                        'entregado_at' => now(),
                    ]);
                    // generar PDF del acta
                    $abs = \App\Services\PdfErp::actaEntregaMaterial($record->fresh(), $data['firma'] ?? null);
                    // notificar al taller + operaciones con el PDF
                    $folio = $record->pedido_id ? (DB::table('pedidos')->where('id', $record->pedido_id)->value('folio') ?: ('#' . $record->pedido_id)) : '';
                    $dest = DB::table('users')->whereIn('rol', ['produccion', 'operaciones'])->where('activo', true)->pluck('email')->filter()->unique()->all();
                    $cuerpo = '<p>Acta de entrega de material del pedido <strong>' . $folio . '</strong>:</p>'
                        . '<p><strong>' . e($mp->nombre) . '</strong>: ' . number_format((float) $record->cantidad, 2) . ' ' . $mp->unidad . '</p>'
                        . '<p>Recibido por: ' . e($data['recibido_nombre'] ?? '—') . '</p>';
                    $html = CorreoBrand::wrap('Acta de entrega de material', $cuerpo);
                    foreach ($dest as $to) {
                        try { Mail::to($to)->send(new DocumentoPedido('Acta entrega material · ' . $folio, $html, [$abs])); } catch (\Throwable $e) { report($e); }
                    }
                    \App\Models\Bitacora::registrar('entregó material', 'Materia prima', $mp->id, $mp->nombre . ' x' . $record->cantidad . ' a ' . ($data['recibido_nombre'] ?? ''));
                    \Filament\Notifications\Notification::make()->success()->title('Material entregado')->body('Acta generada y enviada.')->send();
                    // descargar el PDF
                    return response()->download($abs, 'acta-material-' . $record->id . '.pdf');
                }),
        ])
        ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListSolicitudMaterial::route('/')];
    }
}
