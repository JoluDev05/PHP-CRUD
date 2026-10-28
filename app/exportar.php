<?php
declare(strict_types=1);

require_once __DIR__ . '/promociones.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function exportar_promociones(array $promociones, bool $incluirSolicitante, string $nombreArchivo): void
{
    $libro = new Spreadsheet();
    $hoja = $libro->getActiveSheet();
    $hoja->setTitle('Promociones');

    $encabezados = ['#'];
    if ($incluirSolicitante) {
        $encabezados[] = 'Solicitante';
    }
    array_push($encabezados, 'Producto', 'Importe', 'Moneda', 'Fecha inicio', 'Fecha fin', 'Estatus', 'Comentario');

    foreach ($encabezados as $i => $titulo) {
        $hoja->setCellValueExplicit([$i + 1, 1], $titulo, DataType::TYPE_STRING);
    }

    $fila = 2;
    foreach ($promociones as $p) {
        $valores = [(int) $p['id']];
        if ($incluirSolicitante) {
            $valores[] = $p['solicitante'];
        }
        array_push(
            $valores,
            $p['producto'],
            (float) $p['importe'],
            MONEDAS[(int) $p['moneda']],
            substr($p['fecha_inicio'], 0, 16),
            substr($p['fecha_fin'], 0, 16),
            ESTATUS[(int) $p['estatus']],
            $p['comentario'] ?? ''
        );

        // Explícito para que un texto que empiece con "=" nunca se interprete como fórmula.
        foreach ($valores as $i => $valor) {
            $tipo = is_string($valor) ? DataType::TYPE_STRING : DataType::TYPE_NUMERIC;
            $hoja->setCellValueExplicit([$i + 1, $fila], $valor, $tipo);
        }

        $fila++;
    }

    $total = count($encabezados);
    $columnaImporte = Coordinate::stringFromColumnIndex($incluirSolicitante ? 4 : 3);
    $hoja->getStyle("{$columnaImporte}2:{$columnaImporte}" . max($fila - 1, 2))
        ->getNumberFormat()->setFormatCode('#,##0.00');

    $hoja->getStyle('A1:' . Coordinate::stringFromColumnIndex($total) . '1')->getFont()->setBold(true);
    $hoja->freezePane('A2');

    for ($i = 1; $i <= $total; $i++) {
        $hoja->getColumnDimensionByColumn($i)->setAutoSize(true);
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
    header('Cache-Control: max-age=0');

    (new Xlsx($libro))->save('php://output');
}