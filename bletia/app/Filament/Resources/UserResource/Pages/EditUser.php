<?php
namespace App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function beforeSave(): void
    {
        $record = $this->record;
        $eraUnicoAdmin = $record->rol === 'admin' && User::where('rol', 'admin')->count() <= 1;
        $nuevoRol = $this->data['rol'] ?? $record->rol;

        if ($eraUnicoAdmin && $nuevoRol !== 'admin') {
            Notification::make()
                ->danger()
                ->title('No permitido')
                ->body('Este es el único administrador del sistema; no se le puede quitar el rol de admin.')
                ->persistent()
                ->send();
            $this->halt();
        }
    }
}
