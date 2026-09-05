<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // === Bitácora / Auditoría (v3.3.0) ===
        $modelosAuditar = [
            \App\Models\PedidoEspecial::class,
            \App\Models\Recibo::class,
            \App\Models\Cliente::class,
            \App\Models\Producto::class,
            \App\Models\Proveedor::class,
            \App\Models\Despacho::class,
            \App\Models\User::class,
        ];
        foreach ($modelosAuditar as $m) {
            if (class_exists($m)) $m::observe(\App\Observers\BitacoraObserver::class);
        }
        if (class_exists(\App\Models\MateriaPrima::class)) \App\Models\MateriaPrima::observe(\App\Observers\BitacoraObserver::class);
        if (class_exists(\App\Models\MovimientoMaterial::class)) \App\Models\MovimientoMaterial::observe(\App\Observers\BitacoraObserver::class);
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Login::class, [\App\Listeners\BitacoraLogin::class, 'handleLogin']);
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Logout::class, [\App\Listeners\BitacoraLogin::class, 'handleLogout']);

        // === AjustesOverride: SMTP y Payphone desde BD (Ajustes), con fallback a .env ===
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('ajustes')) {
                $g = fn (string $k) => \App\Models\Ajuste::get($k);

                if ($v = $g('smtp_host'))       config(['mail.mailers.smtp.host' => $v]);
                if ($v = $g('smtp_port'))       config(['mail.mailers.smtp.port' => $v]);
                if ($v = $g('smtp_username'))   config(['mail.mailers.smtp.username' => $v]);
                if ($v = $g('smtp_password'))   config(['mail.mailers.smtp.password' => $v]);
                config(['mail.mailers.smtp.encryption' => $g('smtp_encryption') ?: null]);
                if ($v = $g('smtp_from_address')) config(['mail.from.address' => $v]);
                if ($v = $g('smtp_from_name'))    config(['mail.from.name' => $v]);

                if ($v = $g('payphone_store_id')) config(['payphone.store_id' => $v]);
                if ($v = $g('payphone_token'))    config(['payphone.token' => $v]);
            }
        } catch (\Throwable $e) {
            // Sin conexión a BD todavía (ej. durante composer install / primeras migraciones): usa .env normal.
        }
        //
    }
}
