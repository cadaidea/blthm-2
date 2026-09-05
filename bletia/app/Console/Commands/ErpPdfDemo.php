<?php

namespace App\Console\Commands;

use App\Services\PdfErp;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ErpPdfDemo extends Command
{
    protected $signature = 'erp:pdf {pedido? : ID del pedido (si se omite, toma el último)}';
    protected $description = 'Genera los 6 PDFs del ERP para un pedido y muestra sus URLs';

    public function handle(): int
    {
        $id = $this->argument('pedido');
        $pedido = $id
            ? DB::table('pedidos')->where('id', $id)->first()
            : DB::table('pedidos')->orderByDesc('id')->first();
        if (! $pedido) { $this->error('No hay pedidos.'); return self::FAILURE; }

        $this->info('Generando PDFs del pedido #' . $pedido->id . ' ...');
        try {
            $rutas = PdfErp::todos($pedido);
        } catch (\Throwable $e) {
            $this->error('Error: ' . $e->getMessage());
            return self::FAILURE;
        }
        foreach ($rutas as $tipo => $abs) {
            $this->line(sprintf('  %-18s %s', $tipo, url('storage/erp/' . $pedido->id . '/' . basename($abs))));
        }
        $this->info('Listo. (Si las imágenes no salen, falta el symlink storage o la ruta de fotos.)');
        return self::SUCCESS;
    }
}
