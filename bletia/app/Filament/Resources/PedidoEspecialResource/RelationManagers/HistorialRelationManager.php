<?php
namespace App\Filament\Resources\PedidoEspecialResource\RelationManagers;

use App\Models\PedidoHistorial;
use App\Support\Acl;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class HistorialRelationManager extends RelationManager
{
    protected static string $relationship = 'historial';
    protected static ?string $title = 'Historial / Trazabilidad';
    protected static string|\BackedEnum|null $icon = 'heroicon-o-clock';

    // Solo admin ve el historial completo; los demas ven solo su propio eslabon en los correos/seguimiento
    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        return Acl::esAdmin();
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Fecha/hora')->dateTime('d/m/Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('accion')->label('Acción')->badge()
                    ->formatStateUsing(fn ($state) => PedidoHistorial::ETIQUETAS[$state] ?? $state),
                Tables\Columns\TextColumn::make('user_nombre')->label('Por')->placeholder('—'),
                Tables\Columns\TextColumn::make('rol')->label('Rol')->badge()
                    ->formatStateUsing(fn ($state) => Acl::ROLES[$state] ?? ($state ?: '—'))->color('gray'),
                Tables\Columns\TextColumn::make('nota')->label('Nota')->placeholder('—')->wrap()->limit(60),
            ])
            ->defaultSort('id', 'asc')
            ->paginated(false);
    }
}
