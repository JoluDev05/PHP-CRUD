<?php

declare(strict_types=1);

require __DIR__ . '/../app/auth.php';

$usuario = requerir_login();

$titulo = 'Página de inicio';
require __DIR__ . '/../app/header.php';

?>

<div class="card">
    <h2>Bienvenido, <?= h($usuario['nombre']) ?></h2>
    <p>Tu rol es <strong><?= h($usuario['rol']) ?></strong>.</p>
</div>
<?php require __DIR__ . '/../app/footer.php'; ?>