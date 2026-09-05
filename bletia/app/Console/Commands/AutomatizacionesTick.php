<?php
namespace App\Console\Commands;
use App\Services\Automatizaciones;
use Illuminate\Console\Command;
class AutomatizacionesTick extends Command
{
    protected $signature = 'automatizaciones:tick';
    protected $description = 'Corre automatizaciones diarias (winback, post-purchase, digest)';
    public function handle(): int
    {
        Automatizaciones::winbackDiario();
        Automatizaciones::postPurchaseDiario();
        Automatizaciones::digestDiario();
        Automatizaciones::cumpleanosDiario();
        $this->info('ok');
        return 0;
    }
}
