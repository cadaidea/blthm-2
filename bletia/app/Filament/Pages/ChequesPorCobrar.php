<?php

namespace App\Filament\Pages;
use Filament\Actions;

use App\Models\Recibo;
use Filament\Forms;
use App\Support\Acl;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ChequesPorCobrar extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';
    protected static string|\UnitEnum|null $navigationGroup = 'Contabilidad';
    protected static ?string $navigationLabel = 'Cheques por cobrar';
    protected static ?string $title = 'Cheques por cobrar';
    protected static ?int $navigationSort = 8;
    protected string $view = 'filament.pages.cheques-por-cobrar';

    public static function canAccess(): bool { return static::canViewAny(); }

    public static function canViewAny(): bool
    {
        return in_array(Acl::rol(), ['admin', 'contabilidad', 'operaciones'], true);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function getNavigationBadge(): ?string
    {
        // cantidad de cheques pendientes de cobrar
        $n = Recibo::query()->where('metodo', 'cheque')->where('cheque_cobrado', false)->whereNotIn('cheque_estado', ['rechazado','anulado','cobrado'])->count();
        return $n > 0 ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        // rojo si hay cheques vencidos sin cobrar
        $vencidos = Recibo::query()->where('metodo', 'cheque')->where('cheque_cobrado', false)
            ->whereNotNull('cheque_fecha_cobro')->whereDate('cheque_fecha_cobro', '<', now()->toDateString())->count();
        return $vencidos > 0 ? 'danger' : 'warning';
    }

    protected function getTableQuery(): Builder
    {
        return Recibo::query()->where('metodo', 'cheque');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('cheque_fecha_cobro')->label('Fecha de cobro')->date('d/m/Y')->sortable()
                    ->description(fn (Recibo $r) => $r->cheque_cobrado ? null : self::diasTexto($r)),
                Tables\Columns\TextColumn::make('cheque_numero')->label('N° cheque')->searchable()->placeholder('—'),
                Tables\Columns\TextColumn::make('cheque_banco')->label('Banco')->searchable()->placeholder('—'),
                Tables\Columns\TextColumn::make('cheque_girador')->label('Girador')->searchable()->placeholder('—'),
                Tables\Columns\TextColumn::make('monto')->label('Monto')->money('usd')->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('pedido_id')->label('Pedido')
                    ->formatStateUsing(fn ($state, Recibo $r) => $r->pedido?->folio ?: ('#' . $state))
                    ->url(fn (Recibo $r) => $r->pedido_id ? \App\Filament\Resources\PedidoEspecialResource::getUrl('view', ['record' => $r->pedido_id]) : null)
                    ->color('primary')->weight('bold')->openUrlInNewTab(),
                Tables\Columns\TextColumn::make('estado_cheque')->label('Estado')->badge()
                    ->state(fn (Recibo $r) => self::estado($r))
                    ->color(fn (string $state) => match ($state) {
                        'Cobrado' => 'success',
                        'Vencido', 'Rechazado', 'Anulado' => 'danger',
                        'Vence hoy' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('cheque_fecha_cobro', 'asc')
            ->recordUrl(fn (Recibo $r) => \App\Filament\Resources\ReciboResource\Pages\ViewRecibo::getUrl(['record' => $r->id]))
            ->actions([
                Actions\Action::make('cobrado')->label('Marcar cobrado')->icon('heroicon-o-check-circle')->color('success')
                    ->visible(fn (Recibo $r) => ! $r->cheque_cobrado && Acl::puedeValidarPago())
                    ->modalHeading('Confirmar cobro del cheque')
                    ->modalDescription(fn (Recibo $r) => 'Cheque N° ' . ($r->cheque_numero ?: '—') . ' de ' . ($r->cheque_banco ?: '—') . ' por $' . number_format((float) $r->monto, 2))
                    ->form([
                        Forms\Components\FileUpload::make('cheque_foto_comprobante')->label('Foto del comprobante de depósito')->image()->directory('cheques')->disk('public'),
                        Forms\Components\TextInput::make('cheque_num_deposito')->label('N° de depósito/transacción')->maxLength(60),
                        Forms\Components\TextInput::make('cheque_sustento_sri')->label('Sustento tributario SRI')->maxLength(10)->helperText('Opcional, para el Anexo Transaccional.'),
                    ])
                    ->fillForm(fn (Recibo $r) => [
                        'cheque_foto_comprobante' => $r->cheque_foto_comprobante,
                        'cheque_num_deposito' => $r->cheque_num_deposito,
                        'cheque_sustento_sri' => $r->cheque_sustento_sri,
                    ])
                    ->action(function (Recibo $r, array $data) {
                        $r->update([
                            'cheque_cobrado' => true,
                            'cheque_cobrado_at' => now(),
                            'cheque_estado' => 'cobrado',
                            'cheque_foto_comprobante' => $data['cheque_foto_comprobante'] ?? null,
                            'cheque_num_deposito' => $data['cheque_num_deposito'] ?? null,
                            'cheque_sustento_sri' => $data['cheque_sustento_sri'] ?? null,
                        ]);
                        \App\Models\Bitacora::registrar('cobró cheque', 'Recibo', $r->id, 'Cheque N° ' . ($r->cheque_numero ?: '—') . ' $' . number_format((float) $r->monto, 2));
                        \Filament\Notifications\Notification::make()->success()->title('Cheque marcado como cobrado')->send();
                    }),
                Actions\Action::make('verPedido')->label('Ver pedido')->icon('heroicon-o-eye')->color('gray')
                    ->visible(fn (Recibo $r) => (bool) $r->pedido_id)
                    ->url(fn (Recibo $r) => \App\Filament\Resources\PedidoEspecialResource::getUrl('view', ['record' => $r->pedido_id]))
                    ->openUrlInNewTab(),
            ])
            ->emptyStateHeading('No hay cheques registrados')
            ->emptyStateDescription('Los cheques que registres como pago aparecerán aquí para su seguimiento.')
            ->emptyStateIcon('heroicon-o-banknotes');
    }

    protected static function estado(Recibo $r): string
    {
        if ($r->cheque_estado === 'rechazado') return 'Rechazado';
        if ($r->cheque_estado === 'anulado') return 'Anulado';
        if ($r->cheque_cobrado || $r->cheque_estado === 'cobrado') return 'Cobrado';
        if (! $r->cheque_fecha_cobro) return 'Sin fecha';
        $hoy = now()->startOfDay();
        $fecha = \Illuminate\Support\Carbon::parse($r->cheque_fecha_cobro)->startOfDay();
        if ($fecha->lt($hoy)) return 'Vencido';
        if ($fecha->eq($hoy)) return 'Vence hoy';
        return 'Por vencer';
    }

    protected static function diasTexto(Recibo $r): ?string
    {
        if (! $r->cheque_fecha_cobro) return null;
        $hoy = now()->startOfDay();
        $fecha = \Illuminate\Support\Carbon::parse($r->cheque_fecha_cobro)->startOfDay();
        $dias = $hoy->diffInDays($fecha, false);
        if ($dias < 0) return 'Vencido hace ' . abs($dias) . ' día(s)';
        if ($dias === 0) return 'Cobrar hoy';
        return 'Faltan ' . $dias . ' día(s)';
    }
}
