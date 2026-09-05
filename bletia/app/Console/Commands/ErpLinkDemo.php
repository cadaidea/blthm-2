<?php

namespace App\Console\Commands;

use App\Services\LinksErp;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ErpLinkDemo extends Command
{
    protected $signature = 'erp:link {tipo=cliente_retiro : cliente_retiro|transportista|proveedor} {pedido?}';
    protected $description = 'Crea un link único de confirmación y muestra su URL (para probar el flujo)';

    public function handle(): int
    {
        $tipo = $this->argument('tipo');
        if (! in_array($tipo, ['cliente_retiro', 'transportista', 'proveedor'], true)) {
            $this->error('tipo inválido'); return self::FAILURE;
        }
        $pid = $this->argument('pedido') ?: optional(DB::table('pedidos')->orderByDesc('id')->first())->id;
        $link = LinksErp::crear($tipo, $pid ? (int) $pid : null);
        $this->info('Link ' . $tipo . ' (expira ' . $link->expira_en->format('d/m/Y H:i') . '):');
        $this->line('  ' . LinksErp::url($link));
        return self::SUCCESS;
    }
}
