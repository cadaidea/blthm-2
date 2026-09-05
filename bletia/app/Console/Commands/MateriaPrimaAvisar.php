<?php

namespace App\Console\Commands;

use App\Services\MateriaPrimaAviso;
use Illuminate\Console\Command;

class MateriaPrimaAvisar extends Command
{
    protected $signature = 'materiaprima:avisar';
    protected $description = 'Avisa a producción/operaciones de materias primas en o bajo el stock mínimo';

    public function handle(): int
    {
        $n = MateriaPrimaAviso::bajoMinimo();
        $this->info("Materias primas avisadas: {$n}");
        return self::SUCCESS;
    }
}
