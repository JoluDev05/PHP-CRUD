<?php
declare(strict_types=1);

require __DIR__ . '/../app/auth.php';
require __DIR__ . '/../app/promociones.php';

$usuario = requerir_rol('SOLICITANTE');

$errores = [];
$valores = ['producto' => '', 'importe' => '', 'moneda' => '0', 'fecha_inicio' => '', 'fecha_fin' => '', 'comentario' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valores = array_merge($valores, array_intersect_key($_POST, $valores));
    [$errores, $datos] = validar_promocion($_POST);

    if (!$errores) {
        crear_promocion($datos, $usuario['id']);
        header('Location: nueva.php?ok=1');
        exit;
    }
}

$titulo = 'Nueva promoción';
require __DIR__ . '/../app/header.php';
?>
<div class="card">
    <h2>Nueva solicitud de promoción</h2>

    <?php if (isset($_GET['ok'])): ?>
        <div class="ok">La solicitud se registró con estatus Pendiente.</div>
    <?php endif; ?>

    <?php if ($errores): ?>
        <div class="error">Corrige los campos marcados antes de continuar.</div>
    <?php endif; ?>

    <form method="post" novalidate>
        <label>Producto
            <input type="text" name="producto" maxlength="50" value="<?= h($valores['producto']) ?>">
            <?php if (isset($errores['producto'])): ?><small
                    class="error"><?= h($errores['producto']) ?></small><?php endif; ?>
        </label>

        <label>Importe
            <input type="text" name="importe" inputmode="decimal" placeholder="0.00"
                value="<?= h($valores['importe']) ?>">
            <?php if (isset($errores['importe'])): ?><small
                    class="error"><?= h($errores['importe']) ?></small><?php endif; ?>
        </label>

        <label>Moneda
            <select name="moneda">
                <?php foreach (MONEDAS as $valor => $nombre): ?>
                    <option value="<?= $valor ?>" <?= (string) $valor === (string) $valores['moneda'] ? 'selected' : '' ?>>
                        <?= h($nombre) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errores['moneda'])): ?><small
                    class="error"><?= h($errores['moneda']) ?></small><?php endif; ?>
        </label>

        <label>Fecha de inicio
            <input type="datetime-local" name="fecha_inicio" value="<?= h($valores['fecha_inicio']) ?>">
            <?php if (isset($errores['fecha_inicio'])): ?><small
                    class="error"><?= h($errores['fecha_inicio']) ?></small><?php endif; ?>
        </label>

        <label>Fecha de fin
            <input type="datetime-local" name="fecha_fin" value="<?= h($valores['fecha_fin']) ?>">
            <?php if (isset($errores['fecha_fin'])): ?><small
                    class="error"><?= h($errores['fecha_fin']) ?></small><?php endif; ?>
        </label>

        <label>Comentario (opcional)
            <textarea name="comentario" rows="3" maxlength="255"><?= h($valores['comentario']) ?></textarea>
            <?php if (isset($errores['comentario'])): ?><small
                    class="error"><?= h($errores['comentario']) ?></small><?php endif; ?>
        </label>

        <button type="submit">Registrar solicitud</button>
    </form>
</div>
<?php require __DIR__ . '/../app/footer.php'; ?>