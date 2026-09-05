<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ImageOptimizerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Conversión WebP ahora es explícita por campo (ver App\Support\WebpUpload),
        // no global, para no interferir con FileUpload dentro de Repeaters.
    }
}
