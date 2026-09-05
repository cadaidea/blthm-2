<?php
namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;


use App\Support\Acl;
use App\Filament\Resources\CampaniaResource\Pages;
use App\Models\Campania;
use App\Models\Lista;
use App\Services\Digest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CampaniaResource extends Resource
{
    protected static ?string $model = Campania::class;


    public static function canViewAny(): bool
    {
        return Acl::ve(static::class);
    }
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return \App\Models\Campania::query();
    }
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-paper-airplane';
    protected static string|\UnitEnum|null $navigationGroup = 'Marketing';
    protected static ?string $modelLabel = 'Campaña';
    protected static ?string $pluralModelLabel = 'Campañas';
    protected static ?int $navigationSort = 4;

    public static function canDeleteAny(): bool { return \App\Support\Acl::esAdmin(); }
    public static function canDelete($record): bool { return \App\Support\Acl::esAdmin(); }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make()->columns(2)->columnSpanFull()->schema([
                Forms\Components\TextInput::make('asunto')->required()->columnSpanFull()
                    ->helperText('Variables: {first_name} {last_name} {email} {site_name} {current_year} {cupon}'),
                Forms\Components\TextInput::make('preheader')->label('Texto de vista previa')->columnSpanFull(),
                Forms\Components\Select::make('lista_ids')->label('Listas destino')->multiple()->required()
                    ->options(fn () => Lista::pluck('nombre', 'id')),
                Forms\Components\DateTimePicker::make('programada_at')->label('Programar para (opcional)')
                    ->seconds(false)->helperText('Déjalo vacío para enviar manualmente.'),
                \App\Forms\Components\EditorJsField::make('contenido_json')->label('Contenido')->required()->columnSpanFull()
                    ->helperText('Variables: {first_name} {last_name} {full_name} {email} {site_name} {site_url} {current_year} {cupon}. El header/footer de marca y los enlaces de baja se añaden solos.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('asunto')->searchable()->limit(46),
            Tables\Columns\TextColumn::make('estado')->badge()->color(fn ($state) => match ($state) {
                'enviada' => 'success', 'enviando' => 'info', 'programada' => 'warning', 'fallida' => 'danger', default => 'gray',
            }),
            Tables\Columns\TextColumn::make('total_destinatarios')->label('Dest.'),
            Tables\Columns\TextColumn::make('total_enviados')->label('Enviados'),
            Tables\Columns\TextColumn::make('total_aperturas')->label('Aperturas'),
            Tables\Columns\TextColumn::make('total_clics')->label('Clics'),
            Tables\Columns\TextColumn::make('programada_at')->dateTime('d/m/Y H:i')->label('Programada'),
        ])->defaultSort('created_at', 'desc')
        ->actions([
            Actions\EditAction::make()->visible(fn (Campania $r) => in_array($r->estado, ['borrador', 'programada'], true)),
            Actions\Action::make('prueba')->label('Prueba')->icon('heroicon-o-beaker')
                ->form([Forms\Components\TextInput::make('email')->email()->required()->label('Enviar prueba a')])
                ->action(function (Campania $r, array $data) {
                    try {
                        Digest::enviarPrueba($r, $data['email']);
                        Notification::make()->success()->title('Prueba enviada')->send();
                    } catch (\Throwable $e) {
                        Notification::make()->danger()->title('Error: ' . $e->getMessage())->send();
                    }
                }),
            Actions\Action::make('enviar')->label('Enviar')->icon('heroicon-o-paper-airplane')->color('success')
                ->visible(fn (Campania $r) => in_array($r->estado, ['borrador', 'programada', 'pausada'], true))
                ->requiresConfirmation()
                ->modalDescription('Se encolará a todos los suscriptores confirmados de las listas destino. El cron la envía por lotes.')
                ->action(function (Campania $r) {
                    $n = Digest::encolar($r);
                    Notification::make()->success()->title("Encolada: {$n} destinatarios")->send();
                }),
            Actions\Action::make('pausar')->label('Pausar')->icon('heroicon-o-pause')->color('warning')
                ->visible(fn (Campania $r) => $r->estado === 'enviando')
                ->action(fn (Campania $r) => $r->update(['estado' => 'pausada'])),
            Actions\Action::make('reanudar')->label('Reanudar')->icon('heroicon-o-play')->color('info')
                ->visible(fn (Campania $r) => $r->estado === 'pausada')
                ->action(fn (Campania $r) => $r->update(['estado' => 'enviando'])),
        ])
        ->bulkActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListCampania::route('/'), 'create' => Pages\CreateCampania::route('/create'), 'edit' => Pages\EditCampania::route('/{record}/edit')];
    }
}
