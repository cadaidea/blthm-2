<?php
namespace App\Console\Commands;

use App\Services\CobroSaldo;
use Illuminate\Console\Command;

class CobroRecordatorio extends Command
{
    protected $signature = 'cobro:recordar';
    protected $description = 'Envía recordatorio de cobro a pedidos con saldo pendiente listos para despacho';

    public function handle(): int
    {
        $n = CobroSaldo::recordatorios();
        $this->info("Recordatorios enviados: {$n}");
        return self::SUCCESS;
    }
}
