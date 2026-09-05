<?php
namespace App\Console\Commands;
use App\Services\WooImport;
use Illuminate\Console\Command;
class ErpWooImport extends Command {
    protected $signature = 'erp:woo-import {que=todo : clientes|pedidos|todo}';
    protected $description = 'Importa clientes y/o pedidos desde WooCommerce (seridea.ec)';
    public function handle(): int {
        $que = $this->argument('que');
        $t = WooImport::probar();
        if (! ($t['ok'] ?? false)) { $this->error($t['msg']); return self::FAILURE; }
        if (in_array($que, ['clientes', 'todo'], true)) $this->info('Clientes: ' . (WooImport::importarClientes()['total'] ?? 0));
        if (in_array($que, ['pedidos', 'todo'], true)) $this->info('Pedidos: ' . (WooImport::importarPedidos()['total'] ?? 0));
        return self::SUCCESS;
    }
}
