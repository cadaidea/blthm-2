<?php

namespace App\Console\Commands;

use App\Services\ChequesAviso;
use Illuminate\Console\Command;

class ChequesAvisar extends Command
{
    protected $signature = 'cheques:avisar {--dias=3 : Días de anticipación para avisar}';
    protected $description = 'Avisa a tesorería de los cheques pendientes próximos a vencer o vencidos sin cobrar';

    public function handle(): int
    {
        $n = ChequesAviso::porVencer((int) $this->option('dias'));
        $this->info("Cheques avisados: {$n}");
        return self::SUCCESS;
    }
}
