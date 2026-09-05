<?php
namespace App\Filament\Resources;
use Filament\Actions;

use App\Support\Acl;
use App\Filament\Resources\BounceResource\Pages;
use App\Models\Bounce;
use Filament\Resources\Resource;
use Filament\Tables; use Filament\Tables\Table;
class BounceResource extends Resource {
    protected static ?string $model = Bounce::class;


    public static function canViewAny(): bool
    {
        return Acl::ve(static::class);
    }
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return \App\Models\Bounce::query();
    }
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static string|\UnitEnum|null $navigationGroup = 'Marketing';
    protected static ?string $modelLabel = 'Rebote';
    protected static ?string $pluralModelLabel = 'Rebotes';
    protected static ?int $navigationSort = 5;
    public static function canDeleteAny(): bool { return \App\Support\Acl::esAdmin(); }
    public static function canDelete($record): bool { return \App\Support\Acl::esAdmin(); }

    public static function table(Table $table): Table {
        return $table->columns([
            Tables\Columns\TextColumn::make('email')->searchable()->copyable(),
            Tables\Columns\TextColumn::make('tipo')->badge()->color(fn ($state) => match ($state) { 'hard' => 'danger', 'complaint' => 'warning', default => 'gray' }),
            Tables\Columns\TextColumn::make('reason')->label('Motivo')->limit(60),
            Tables\Columns\TextColumn::make('source')->label('Origen'),
            Tables\Columns\TextColumn::make('created_at')->dateTime('d/m/Y H:i')->label('Fecha')->sortable(),
        ])->defaultSort('created_at', 'desc')
          ->filters([Tables\Filters\SelectFilter::make('tipo')->options(['hard' => 'Hard', 'soft' => 'Soft', 'complaint' => 'Queja'])])
          ->actions([])
          ->bulkActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])]);
    }
    public static function getPages(): array { return ['index' => Pages\ListBounce::route('/')]; }
}
