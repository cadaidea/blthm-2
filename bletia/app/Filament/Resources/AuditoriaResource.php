<?php
namespace App\Filament\Resources;

use App\Filament\Resources\AuditoriaResource\Pages;
use App\Models\Bitacora;
use App\Support\Acl;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AuditoriaResource extends Resource
{
    protected static ?string $model = Bitacora::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'Auditoría';
    protected static ?string $modelLabel = 'registro de auditoría';
    protected static ?string $pluralModelLabel = 'auditoría';
    protected static string|\UnitEnum|null $navigationGroup = 'Sistema';
    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool { return Acl::esAdmin() || Acl::esOperaciones(); }
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
            Tables\Columns\TextColumn::make('user_nombre')->label('Empleado')->searchable()->placeholder('—')
                ->description(fn ($record) => $record->rol),
            Tables\Columns\TextColumn::make('evento')->label('Evento')->badge()
                ->color(fn ($state) => match ($state) {
                    'creó' => 'success', 'eliminó' => 'danger', 'actualizó' => 'info',
                    'inició sesión' => 'gray', 'cerró sesión' => 'gray', default => 'warning',
                }),
            Tables\Columns\TextColumn::make('modulo')->label('Módulo')->badge()->color('gray')->searchable(),
            Tables\Columns\TextColumn::make('registro_id')->label('#')->placeholder('—'),
            Tables\Columns\TextColumn::make('descripcion')->label('Detalle')->placeholder('—')->wrap()->limit(60),
            Tables\Columns\TextColumn::make('ip')->label('IP')->toggleable(isToggledHiddenByDefault: true),
        ])
        ->filters([
            Tables\Filters\SelectFilter::make('modulo')->label('Módulo')
                ->options(fn () => Bitacora::query()->distinct()->orderBy('modulo')->pluck('modulo', 'modulo')->filter()->all()),
            Tables\Filters\SelectFilter::make('evento')->label('Evento')
                ->options(['creó'=>'Creó','actualizó'=>'Actualizó','eliminó'=>'Eliminó','inició sesión'=>'Inició sesión','cerró sesión'=>'Cerró sesión']),
        ])
        ->defaultSort('created_at', 'desc')
        ->actions([]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListAuditoria::route('/')];
    }
}
