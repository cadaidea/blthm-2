<?php
namespace App\Support;

use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class WebpUpload
{
    /**
     * Aplica conversión automática a WebP en un FileUpload fuera de Repeater.
     * Uso: ->saveUploadedFileUsing(WebpUpload::handler())
     */
    public static function handler(): \Closure
    {
        return function (TemporaryUploadedFile $file, FileUpload $component) {
            $disco = $component->getDiskName() ?: 'public';
            $directorio = trim((string) $component->getDirectory(), '/');
            $visibilidad = $component->getVisibility() ?: 'public';

            $guardarOriginal = fn () => $file->store($directorio, ['disk' => $disco, 'visibility' => $visibilidad]);

            $mime = $file->getMimeType();
            if ($mime === 'image/svg+xml' || ! str_starts_with((string) $mime, 'image/')) {
                return $guardarOriginal();
            }

            try {
                $manager = ImageManager::gd();
                $imagen = $manager->read($file->getRealPath());
                $imagen->scaleDown(width: 1920, height: 1920);
                $codificada = $imagen->encode(new WebpEncoder(quality: 82));
            } catch (\Throwable $e) {
                report($e);
                return $guardarOriginal();
            }

            $nombre = (string) Str::uuid() . '.webp';
            $ruta = ($directorio ? $directorio . '/' : '') . $nombre;
            Storage::disk($disco)->put($ruta, (string) $codificada, $visibilidad);
            return $ruta;
        };
    }
}
