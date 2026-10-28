<?php
declare(strict_types=1);

require __DIR__ . '/../app/auth.php';
require __DIR__ . '/../app/importar.php';

$usuario = requerir_rol('SOLICITANTE');

$errorGeneral = null;
$erroresFila = [];
$importadas = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $archivo = $_FILES['archivo'] ?? null;

    if (!$archivo || $archivo['error'] !== UPLOAD_ERR_OK) {
        $errorGeneral = 'No se pudo subir el archivo. Selecciona un .xlsx.';
    } elseif (strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION)) !== 'xlsx') {
        $errorGeneral = 'Solo se aceptan archivos .xlsx.';
    } elseif ($archivo['size'] > 2 * 1024 * 1024) {
        $errorGeneral = 'El archivo no puede pesar más de 2 MB.';
    } else {
        try {
            $filas = leer_excel($archivo['tmp_name']);

            if (!$filas) {
                $errorGeneral = 'El archivo no tiene filas con datos.';
            } else {
                [$erroresFila, $validos] = validar_importacion($filas);

                if (!$erroresFila) {
                    $importadas = importar_promociones($validos, $usuario['id']);
                }
            }
        } catch (RuntimeException $e) {
            $errorGeneral = $e->getMessage();
        } catch (Throwable $e) {
            $errorGeneral = 'No se pudo leer el archivo. Verifica que sea un .xlsx válido.';
        }
    }
}

$titulo = 'Importar desde Excel';
require __DIR__ . '/../app/header.php';
?>
<div class="card">
    <h2>Importar solicitudes desde Excel</h2>

    <p>
        1. <a href="plantilla.php">Descarga la plantilla</a>.<br>
        2. Llénala (una promoción por fila; fechas con formato <code>AAAA-MM-DD HH:MM</code>; moneda MXN o USD).<br>
        3. Súbela aquí. Se registrarán todas como <strong>Pendiente</strong>.
    </p>

    <?php if ($importadas !== null): ?>
        <div class="ok">
            Se importaron <?= $importadas ?> solicitud(es) con estatus Pendiente.
            <a href="index.php?estatus=0">Ver mis promociones</a>
        </div>
    <?php endif; ?>

    <?php if ($errorGeneral): ?>
        <div class="error"><?= h($errorGeneral) ?></div>
    <?php endif; ?>

    <?php if ($erroresFila): ?>
        <div class="error">
            <strong>No se importó nada.</strong> Corrige estas filas y vuelve a subir el archivo:
            <ul>
                <?php foreach ($erroresFila as $numero => $mensajes): ?>
                    <li>Fila <?= (int) $numero ?>: <?= h(implode(' ', $mensajes)) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <label>Archivo Excel (.xlsx)
            <input type="file" name="archivo" accept=".xlsx" required>
        </label>
        <button type="submit">Importar</button>
    </form>
</div>
<?php require __DIR__ . '/../app/footer.php'; ?>