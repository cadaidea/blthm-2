<?php
namespace App\Listeners;

use App\Models\Bitacora;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

class BitacoraLogin
{
    public function handleLogin(Login $event): void
    {
        Bitacora::registrar('inició sesión', 'Acceso', $event->user->id ?? null, null);
    }

    public function handleLogout(Logout $event): void
    {
        Bitacora::registrar('cerró sesión', 'Acceso', $event->user->id ?? null, null);
    }
}
