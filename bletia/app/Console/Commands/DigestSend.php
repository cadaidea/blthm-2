<?php

namespace App\Console\Commands;

use App\Services\Digest;
use Illuminate\Console\Command;

class DigestSend extends Command
{
    protected $signature = 'digest:send';
    protected $description = 'Procesa la cola de campañas (lote con throttle)';

    public function handle(): int
    {
        $r = Digest::procesarLote();
        $this->info('Enviados: ' . ($r['enviados'] ?? 0) . (isset($r['motivo']) ? ' (' . $r['motivo'] . ')' : ''));
        return self::SUCCESS;
    }
}
