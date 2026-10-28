<?php
declare(strict_types=1);

require __DIR__ . '/../app/auth.php';
require __DIR__ . '/../app/promociones.php';

requerir_rol('ADMIN');

$id = (int) ($_GET['id'] ?? 0);
$promo = obtener_promocion($id);

if (!$promo) {
    http_response_code(404);
    exit('Promoción no encontrada.');
}

if ((int) $promo['estatus'] !== 0) {
    header('Location: index.php');
    exit;
}

$error = null;
$motivo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $motivo = trim((string) ($_POST['motivo'] ?? ''));

    if ($accion === 'aprobar') {
        resolver_promocion($id, 1, null);
        header('Location: index.php?estatus=1');
        exit;
    }

    if ($accion === 'rechazar') {
        if ($motivo === '') {
            $error = 'Debes indicar el motivo del rechazo.';
        } elseif (mb_strlen($motivo) > 255) {
            $error = 'El motivo no puede pasar de 255 caracteres.';
        } else {
            resolver_promocion($id, 3, $motivo);
            header('Location: index.php?estatus=3');
            exit;
        }
    }
}

$titulo = 'Revisar promoción';
require __DIR__ . '/../app/header.php';
?>
<div class="card">
    <h2>Revisar promoción #<?= $id ?></h2>

    <p><strong>Solicitante:</strong> <?= h($promo['solicitante']) ?></p>
    <p><strong>Producto:</strong> <?= h($promo['producto']) ?></p>
    <p><strong>Importe:</strong> <?= h(formatear_importe($promo['importe'], (int) $promo['moneda'])) ?></p>
    <p><strong>Vigencia:</strong> <?= h(formatear_fecha($promo['fecha_inicio'])) ?> –
        <?= h(formatear_fecha($promo['fecha_fin'])) ?></p>
    <p><strong>Comentario del solicitante:</strong> <?= h($promo['comentario'] ?? '—') ?></p>

    <?php if ($error): ?>
        <div class="error"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <label>Motivo (obligatorio solo si rechazas)
            <textarea name="motivo" rows="3" maxlength="255"><?= h($motivo) ?></textarea>
        </label>

        <button type="submit" name="accion" value="aprobar">Aprobar</button>
        <button type="submit" name="accion" value="rechazar" class="peligro">Rechazar</button>
        <a href="index.php" class="btn gris">Cancelar</a>
    </form>
</div>
<?php require __DIR__ . '/../app/footer.php'; ?>