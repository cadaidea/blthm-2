<?php

namespace App\Console\Commands;

use App\Models\SriComprobante;
use App\Services\Sri\EnviarComprobante;
use App\Services\Sri\Ride;
use Illuminate\Console\Command;

class SriRide extends Command
{
    protected $signature = 'sri:ride {id?} {--email=} {--no-mail}';
    protected $description = 'Genera el RIDE (PDF) y opcionalmente lo envía por correo.';

    public function handle(): int
    {
        $c = $this->argument('id') ? SriComprobante::find($this->argument('id')) : SriComprobante::where('estado', 'AUTORIZADO')->latest('id')->first();
        if (! $c) { $this->error('Sin comprobante.'); return self::FAILURE; }
        if ($this->option('email')) $c->receptor_email = $this->option('email');

        $path = Ride::generar($c);
        $this->info('RIDE generado: ' . $path);

        if (! $this->option('no-mail')) {
            $r = EnviarComprobante::procesar($c->fresh(), true);
            $this->line(($r['ok'] ? '✓ ' : '✗ ') . $r['msg']);
        }
        return self::SUCCESS;
    }
}
