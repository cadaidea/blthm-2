<?php
namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

/**
 * Genera .xlsx reales. Todo como TEXTO (sin fórmulas), para respaldo.
 * Compatible con PhpSpreadsheet 2+ (sin getCellByColumnAndRow).
 */
class ExportadorExcel
{
    public static function generar(array $hojas, string $nombreArchivo): string
    {
        $ss = new Spreadsheet();
        $ss->removeSheetByIndex(0);
        $i = 0;
        foreach ($hojas as $titulo => $data) {
            $sheet = $ss->createSheet($i++);
            $sheet->setTitle(mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', (string) $titulo), 0, 31) ?: ('Hoja' . $i));
            $headers = array_values($data['headers'] ?? []);
            $rows = $data['rows'] ?? [];

            // cabecera
            $col = 1;
            foreach ($headers as $h) {
                $ref = Coordinate::stringFromColumnIndex($col) . '1';
                $sheet->setCellValueExplicit($ref, (string) $h, DataType::TYPE_STRING);
                $col++;
            }
            if ($headers) {
                $lastCol = Coordinate::stringFromColumnIndex(count($headers));
                $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
                $sheet->getStyle("A1:{$lastCol}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('161921');
                $sheet->getStyle("A1:{$lastCol}1")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                for ($c = 1; $c <= count($headers); $c++) {
                    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setWidth(22);
                }
            }

            // filas (todo texto)
            $r = 2;
            foreach ($rows as $row) {
                $col = 1;
                foreach ($row as $val) {
                    if (is_array($val)) $val = implode(', ', $val);
                    $ref = Coordinate::stringFromColumnIndex($col) . $r;
                    $sheet->setCellValueExplicit($ref, $val === null ? '' : (string) $val, DataType::TYPE_STRING);
                    $col++;
                }
                $r++;
            }
            $sheet->freezePane('A2');
        }
        if ($ss->getSheetCount() === 0) $ss->createSheet(0)->setTitle('Vacio');
        $ss->setActiveSheetIndex(0);

        $dir = storage_path('app/exports');
        if (! is_dir($dir)) @mkdir($dir, 0775, true);
        $path = $dir . '/' . $nombreArchivo;
        (new Xlsx($ss))->save($path);
        return $path;
    }

    public static function deRegistros(iterable $registros, array $cols): array
    {
        $headers = array_values($cols);
        $rows = [];
        foreach ($registros as $reg) {
            $fila = [];
            foreach (array_keys($cols) as $campo) {
                $v = data_get($reg, $campo);
                if (is_array($v)) $v = implode(', ', $v);
                $fila[] = $v;
            }
            $rows[] = $fila;
        }
        return ['headers' => $headers, 'rows' => $rows];
    }
}
