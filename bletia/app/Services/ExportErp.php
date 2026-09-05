<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportErp
{
    /** Pestañas a exportar: nombre visible => tabla. */
    protected static function hojas(): array
    {
        return [
            'Pedidos'        => 'pedidos',
            'Despachos'      => 'despachos',
            'Confirmaciones' => 'confirmaciones',
            'Pedido ítems'   => 'pedido_items',
            'Proveedores'    => 'proveedores',
            'Transportistas' => 'transportistas',
            'Movimientos'    => 'movimientos_stock',
            'Suscriptores'   => 'suscriptores',
        ];
    }

    public static function generar(?string $desde = null, ?string $hasta = null, int $limite = 5000): string
    {
        $book = new Spreadsheet();
        $book->removeSheetByIndex(0);

        foreach (self::hojas() as $titulo => $tabla) {
            if (! Schema::hasTable($tabla)) continue;

            $q = DB::table($tabla);
            if ($desde && Schema::hasColumn($tabla, 'created_at')) $q->where('created_at', '>=', $desde);
            if ($hasta && Schema::hasColumn($tabla, 'created_at')) $q->where('created_at', '<=', $hasta . ' 23:59:59');
            $rows = $q->limit($limite)->get();

            $sheet = $book->createSheet();
            $sheet->setTitle(mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', $titulo), 0, 31));

            if ($rows->isEmpty()) {
                $sheet->setCellValue('A1', 'Sin datos');
                continue;
            }
            $headers = array_keys((array) $rows->first());
            $col = 1;
            foreach ($headers as $h) {
                $sheet->setCellValueByColumnAndRow($col++, 1, $h);
            }
            $r = 2;
            foreach ($rows as $row) {
                $col = 1;
                foreach ((array) $row as $val) {
                    if (is_array($val) || is_object($val)) $val = json_encode($val, JSON_UNESCAPED_UNICODE);
                    $sheet->setCellValueByColumnAndRow($col++, $r, $val);
                }
                $r++;
            }
            foreach (range(1, count($headers)) as $c) {
                $sheet->getColumnDimensionByColumn($c)->setAutoSize(true);
            }
            $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->getFont()->setBold(true);
        }

        if ($book->getSheetCount() === 0) {
            $book->createSheet()->setTitle('Vacío')->setCellValue('A1', 'Sin tablas');
        }

        $dir = storage_path('app/public/erp/export');
        File::ensureDirectoryExists($dir);
        $archivo = 'export-' . now()->format('Ymd-His') . '.xlsx';
        $abs = $dir . '/' . $archivo;
        (new Xlsx($book))->save($abs);
        $book->disconnectWorksheets();

        return $archivo;
    }

    public static function url(string $archivo): string
    {
        return url('storage/erp/export/' . $archivo);
    }
}
