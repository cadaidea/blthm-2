<?php
namespace App\Filament\Pages;
use Filament\Schemas\Schema;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Auth\Pages\EditProfile;

/**
 * Página de perfil propia: cada empleado edita su nombre, email, contraseña y SU foto.
 */
class PerfilBletia extends EditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            FileUpload::make('avatar')->label('Mi foto de perfil')->image()->avatar()
                ->directory('avatars')->disk('public')->imageEditor()->circleCropper(),
            $this->getNameFormComponent(),
            $this->getEmailFormComponent(),
            $this->getPasswordFormComponent(),
            $this->getPasswordConfirmationFormComponent(),
        ]);
    }
}
