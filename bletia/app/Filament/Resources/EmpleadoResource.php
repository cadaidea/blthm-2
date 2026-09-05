<?php
namespace App\Filament\Resources;
use Filament\Actions;
use Filament\Schemas\Schema;

use App\Support\Acl;
use App\Filament\Resources\EmpleadoResource\Pages;
use App\Models\Empleado;
use App\Models\Local;
use App\Models\User;
use Filament\Forms; use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables; use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class EmpleadoResource extends Resource
{
    protected static ?string $model = Empleado::class;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['user']);
    }

    public static function canViewAny(): bool { return Acl::esAdmin(); }
    public static function canDelete($record): bool { return false; }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';
    protected static string|\UnitEnum|null $navigationGroup = 'Nómina / RRHH';
    protected static ?string $modelLabel = 'Colaborador';
    protected static ?string $pluralModelLabel = 'Colaboradores';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Tabs::make('Colaborador')->columnSpanFull()->tabs([

                \Filament\Schemas\Components\Tabs\Tab::make('Datos personales')->columns(2)->schema([
                    Forms\Components\TextInput::make('nombre')->required()->columnSpanFull(),
                    Forms\Components\Select::make('tipo_identificacion')->options(['cedula' => 'Cédula', 'ruc' => 'RUC', 'pasaporte' => 'Pasaporte'])->default('cedula')->native(false),
                    Forms\Components\TextInput::make('identificacion')->maxLength(20),
                    Forms\Components\TextInput::make('cargo'),
                    Forms\Components\TextInput::make('email')->email(),
                    Forms\Components\TextInput::make('telefono')->tel(),
                    Forms\Components\TextInput::make('direccion')->columnSpanFull(),
                ]),

                \Filament\Schemas\Components\Tabs\Tab::make('Relación laboral')->columns(2)->schema([
                    Forms\Components\Select::make('relacion')->label('Tipo de relación')->required()->default('dependencia')
                        ->options(Empleado::RELACIONES)->live()->native(false),
                    Forms\Components\Select::make('tipo_contrato')->label('Tipo de contrato (Código de Trabajo)')
                        ->options(Empleado::TIPOS_CONTRATO)->native(false)
                        ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('relacion') === 'dependencia')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('relacion') === 'dependencia'),
                    Forms\Components\TextInput::make('sueldo')->label('Sueldo / honorario mensual')->numeric()->prefix('$')->default(0)
                        ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('relacion') !== 'colaborador')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('relacion') !== 'colaborador')
                        ->helperText('Los colaboradores no tienen sueldo fijo; se les registra incentivos puntuales.'),
                    Forms\Components\DatePicker::make('fecha_ingreso'),
                    Forms\Components\DatePicker::make('fecha_salida')->helperText('Solo si ya no trabaja aquí.'),
                    Forms\Components\TextInput::make('cargas_familiares')->numeric()->default(0),
                    Forms\Components\TextInput::make('dias_vacaciones_anuales')->label('Días de vacaciones al año')->numeric()->default(15)
                        ->helperText('Ley: mínimo 15. Sube el número si tiene más por convenio/antigüedad.'),
                    Forms\Components\Select::make('region')->label('Región (para décimo cuarto)')
                        ->options(['sierra_oriente' => 'Sierra / Oriente (pago en marzo)', 'costa_galapagos' => 'Costa / Galápagos (pago en agosto)'])
                        ->default('sierra_oriente')->native(false)
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('relacion') === 'dependencia'),
                    Forms\Components\Select::make('modo_decimo_tercero')->label('Décimo tercero')
                        ->options(['acumulado' => 'Acumulado (pago en diciembre)', 'mensualizado' => 'Mensualizado (en cada rol)'])
                        ->default('acumulado')->native(false)
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('relacion') === 'dependencia'),
                    Forms\Components\Select::make('modo_decimo_cuarto')->label('Décimo cuarto')
                        ->options(['acumulado' => 'Acumulado (marzo o agosto)', 'mensualizado' => 'Mensualizado (en cada rol)'])
                        ->default('acumulado')->native(false)
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('relacion') === 'dependencia'),
                    Forms\Components\Select::make('modo_fondos_reserva')->label('Fondos de reserva (desde el 2º año)')
                        ->options(['mensualizado' => 'Mensualizado (en cada rol)', 'acumulado' => 'Acumulado (pago anual)'])
                        ->default('mensualizado')->native(false)
                        ->helperText('Solo se paga tras cumplir 1 año. El sistema lo activa solo por la fecha de ingreso.')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('relacion') === 'dependencia'),
                    Forms\Components\Toggle::make('activo')->default(true),
                ]),

                \Filament\Schemas\Components\Tabs\Tab::make('Acceso al sistema')->columns(2)->schema([
                    Forms\Components\Toggle::make('tiene_acceso')->label('¿Tiene acceso al panel?')->live()->default(false)
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Forms\Components\Toggle $component, $state, $record) {
                            $component->state((bool) ($record?->user_id));
                        })
                        ->columnSpanFull(),
                    Forms\Components\Select::make('rol_sistema')->label('Rol en el sistema')->options(Acl::ROLES)->native(false)
                        ->dehydrated(false)
                        ->afterStateHydrated(fn ($component, $record) => $component->state($record?->user?->rol))
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tiene_acceso'))
                        ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tiene_acceso')),
                    Forms\Components\Select::make('local_id_sistema')->label('Local')
                        ->options(fn () => Local::where('activo', true)->pluck('nombre', 'id'))->searchable()
                        ->dehydrated(false)
                        ->afterStateHydrated(fn ($component, $record) => $component->state($record?->user?->local_id))
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tiene_acceso')),
                    Forms\Components\TextInput::make('password_sistema')->label('Contraseña')->password()->revealable()
                        ->dehydrated(false)
                        ->helperText('Déjalo vacío para no cambiarla. Obligatoria solo la primera vez.')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('tiene_acceso')),
                ]),

                \Filament\Schemas\Components\Tabs\Tab::make('Perfil de autor (blog)')->columns(2)->schema([
                    Forms\Components\Toggle::make('es_autor')->label('¿Escribe artículos en el blog?')->live()->default(false)
                        ->dehydrated(false)
                        ->afterStateHydrated(fn ($component, $record) => $component->state((bool) ($record?->slug)))
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('slug')->label('URL del perfil público')->helperText('Ej: isbaal → /@isbaal')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('es_autor')),
                    Forms\Components\FileUpload::make('foto')->label('Foto de perfil')->image()->avatar()->directory('empleados')->disk('public')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('es_autor')),
                    Forms\Components\Textarea::make('bio')->label('Biografía pública')->rows(2)->columnSpanFull()
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('es_autor')),
                    Forms\Components\TextInput::make('web')->url()->prefix('https://')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('es_autor')),
                    Forms\Components\TextInput::make('instagram')->url()->prefix('https://')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('es_autor')),
                    Forms\Components\TextInput::make('facebook')->url()->prefix('https://')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('es_autor')),
                    Forms\Components\TextInput::make('x')->label('X (Twitter)')->url()->prefix('https://')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('es_autor')),
                    Forms\Components\TextInput::make('linkedin')->url()->prefix('https://')
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('es_autor')),
                ]),

                \Filament\Schemas\Components\Tabs\Tab::make('Pago')->columns(3)->schema([
                    Forms\Components\TextInput::make('banco'),
                    Forms\Components\Select::make('tipo_cuenta')->options(['ahorros' => 'Ahorros', 'corriente' => 'Corriente'])->native(false),
                    Forms\Components\TextInput::make('cuenta_bancaria'),
                    Forms\Components\Textarea::make('notas')->rows(2)->columnSpanFull(),
                ]),

            ]),
        ]);
    }

    /** Crea/actualiza el User vinculado según lo elegido en la pestaña "Acceso al sistema". */
    public static function sincronizarAcceso(Empleado $empleado, array $rawData): void
    {
        $tiene = (bool) ($rawData['tiene_acceso'] ?? false);

        if (! $tiene) {
            return; // no se toca ni se borra el user existente si lo desactivan; solo no se crea uno nuevo
        }

        if (empty($empleado->email)) {
            \Filament\Notifications\Notification::make()->warning()
                ->title('No se creó el acceso')->body('Falta el email en "Datos personales".')->send();
            return;
        }

        $rol = $rawData['rol_sistema'] ?? 'vendedor';
        $localId = $rawData['local_id_sistema'] ?? null;
        $pass = $rawData['password_sistema'] ?? null;

        $user = $empleado->user ?: User::where('email', $empleado->email)->first();

        $datos = [
            'name' => $empleado->nombre,
            'email' => $empleado->email,
            'rol' => $rol,
            'local_id' => $localId,
            'activo' => (bool) $empleado->activo,
        ];
        if ($pass) $datos['password'] = Hash::make($pass);

        if ($user) {
            $user->update($datos);
        } else {
            if (! $pass) {
                \Filament\Notifications\Notification::make()->warning()
                    ->title('No se creó el acceso')->body('Define una contraseña para crear la cuenta nueva.')->send();
                return;
            }
            $user = User::create($datos);
        }

        if ($empleado->user_id !== $user->id) {
            $empleado->user_id = $user->id;
            $empleado->saveQuietly();
        }
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('nombre')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('cargo')->placeholder('—'),
            Tables\Columns\TextColumn::make('relacion')->badge()
                ->formatStateUsing(fn ($state) => Empleado::RELACIONES[$state] ?? $state)
                ->color(fn ($state) => match ($state) {
                    'dependencia' => 'success', 'honorarios' => 'warning', 'colaborador' => 'gray', default => 'gray',
                }),
            Tables\Columns\TextColumn::make('user.rol')->label('Rol sistema')->badge()
                ->formatStateUsing(fn ($state) => $state ? (Acl::ROLES[$state] ?? $state) : 'Sin acceso')
                ->color(fn ($state) => $state ? 'primary' : 'gray'),
            Tables\Columns\TextColumn::make('sueldo')->money('usd')->sortable(),
            Tables\Columns\IconColumn::make('activo')->boolean(),
        ])
        ->defaultSort('nombre')
        ->filters([
            Tables\Filters\SelectFilter::make('relacion')->options(Empleado::RELACIONES),
            Tables\Filters\TernaryFilter::make('activo'),
        ])
        ->actions([Actions\EditAction::make()])
        ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListEmpleados::route('/'),
            'create' => Pages\CreateEmpleado::route('/create'),
            'edit'   => Pages\EditEmpleado::route('/{record}/edit'),
        ];
    }
}
