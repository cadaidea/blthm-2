<?php

namespace App\Console\Commands;

use App\Services\ExportErp;
use Illuminate\Console\Command;

class ErpExport extends Command
{
    protected $signature = 'erp:export {desde?} {hasta?}';
    protected $description = 'Genera un Excel multi-pestaña del ERP y muestra su URL';

    public function handle(): int
    {
        $archivo = ExportErp::generar($this->argument('desde'), $this->argument('hasta'));
        $this->info('Excel generado:');
        $this->line('  ' . ExportErp::url($archivo));
        return self::SUCCESS;
    }
}
