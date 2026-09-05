<?php

namespace App\Console\Commands;

use App\Models\SriComprobante;
use Illuminate\Console\Command;

class SriDumpXml extends Command
{
    protected $signature = 'sri:dump {id?}';
    protected $description = 'Vuelca el XML firmado del último comprobante a un archivo para inspección.';

    public function handle(): int
    {
        $c = $this->argument('id') ? SriComprobante::find($this->argument('id')) : SriComprobante::latest('id')->first();
        if (! $c) { $this->error('Sin comprobante.'); return self::FAILURE; }
        $path = storage_path('app/sri/firmado_' . $c->id . '.xml');
        file_put_contents($path, $c->xml_firmado ?? '');
        $this->info('XML firmado guardado en: ' . $path);
        $this->line('Clave: ' . $c->clave_acceso . ' · estado: ' . $c->estado);
        return self::SUCCESS;
    }
}
