<?php
declare(strict_types=1);

require_once __DIR__ . '/promociones.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

const COLUMNAS_PLANTILLA = ['producto', 'importe', 'moneda', 'fecha_inicio', 'fecha_fin', 'comentario'];
const MAX_FILAS_IMPORTAR = 500;

function generar_plantilla(): void
{
    $libro = new Spreadsheet();
    $hoja = $libro->getActiveSheet();
    $hoja->setTitle('Promociones');

    $hoja->fromArray(COLUMNAS_PLANTILLA, null, 'A1');
    $hoja->fromArray(
        ['Laptop', 1500.50, 'MXN', '2026-11-01 09:00', '2026-11-30 18:00', 'Ejemplo: borra esta fila'],
        null,
        'A2'
    );

    // Las fechas se capturan como texto AAAA-MM-DD HH:MM para que Excel no las reinterprete.
    $hoja->getStyle('D2:E501')->getNumberFormat()->setFormatCode('@');
    $hoja->getStyle('B2:B501')->getNumberFormat()->setFormatCode('#,##0.00');

    $lista = new DataValidation();
    $lista->setType(DataValidation::TYPE_LIST);
    $lista->setAllowBlank(false);
    $lista->setShowDropDown(true);
    $lista->setFormula1('"MXN,USD"');
    $hoja->setDataValidation('C2:C501', $lista);

    $hoja->getStyle('A1:F1')->getFont()->setBold(true);
    foreach (['A' => 28, 'B' => 14, 'C' => 10, 'D' => 20, 'E' => 20, 'F' => 40] as $columna => $ancho) {
        $hoja->getColumnDimension($columna)->setWidth($ancho);
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="plantilla_promociones.xlsx"');
    header('Cache-Control: max-age=0');

    (new Xlsx($libro))->save('php://output');
}

/** Pasa lo que Excel entrega (números, fechas serie, texto) a lo que espera validar_promocion(). */
function normalizar_fila(array $fila): array
{
    if (is_int($fila['importe']) || is_float($fila['importe'])) {
        $fila['importe'] = rtrim(rtrim(number_format((float) $fila['importe'], 6, '.', ''), '0'), '.');
    }

    $moneda = strtoupper(trim((string) $fila['moneda']));
    $fila['moneda'] = match ($moneda) {
        'MXN', '0' => '0',
        'USD', '1' => '1',
        default => $moneda,
    };

    foreach (['fecha_inicio', 'fecha_fin'] as $campo) {
        if (is_int($fila[$campo]) || is_float($fila[$campo])) {
            $fila[$campo] = ExcelDate::excelToDateTimeObject($fila[$campo])->format('Y-m-d H:i:s');
        }
    }

    return $fila;
}

/** Devuelve [['fila' => número en Excel, 'datos' => [...]], ...]. Lanza RuntimeException si no es la plantilla. */
function leer_excel(string $ruta): array
{
    $lector = IOFactory::createReader('Xlsx');
    $lector->setReadDataOnly(true);
    $filas = $lector->load($ruta)->getActiveSheet()->toArray(null, true, false, false);

    $total = count(COLUMNAS_PLANTILLA);
    $encabezado = array_map(
        fn($celda) => strtolower(trim((string) $celda)),
        array_slice($filas[0] ?? [], 0, $total)
    );

    if ($encabezado !== COLUMNAS_PLANTILLA) {
        throw new RuntimeException('El archivo no usa la plantilla. Descárgala de nuevo y no cambies los encabezados.');
    }

    $resultado = [];

    foreach (array_slice($filas, 1, null, true) as $indice => $fila) {
        $celdas = array_slice(array_pad($fila, $total, null), 0, $total);

        $vacia = implode('', array_map(fn($celda) => trim((string) $celda), $celdas)) === '';
        if ($vacia) {
            continue;
        }

        if (count($resultado) >= MAX_FILAS_IMPORTAR) {
            throw new RuntimeException('El archivo tiene más de ' . MAX_FILAS_IMPORTAR . ' filas.');
        }

        $resultado[] = [
            'fila' => $indice + 1,
            'datos' => normalizar_fila(array_combine(COLUMNAS_PLANTILLA, $celdas)),
        ];
    }

    return $resultado;
}

/** Devuelve [errores por fila, datos válidos]. */
function validar_importacion(array $filas): array
{
    $errores = [];
    $validos = [];

    foreach ($filas as $fila) {
        [$erroresFila, $datos] = validar_promocion($fila['datos']);

        if ($erroresFila) {
            $errores[$fila['fila']] = $erroresFila;
        } else {
            $validos[] = $datos;
        }
    }

    return [$errores, $validos];
}

/** Todo o nada: si algo falla, no se guarda ninguna. */
function importar_promociones(array $validos, int $usuarioId): int
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        foreach ($validos as $datos) {
            crear_promocion($datos, $usuarioId);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    return count($validos);
}