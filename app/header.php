<?php $usuario = usuario_actual(); ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($titulo ?? 'Promociones') ?></title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <?php if ($usuario): ?>
        <nav class="nav">
            <strong>Promociones internas</strong>
            <a href="index.php">Inicio</a>
            <?php if ($usuario['rol'] === 'SOLICITANTE'): ?>
                <a href="nueva.php">Nueva promoción</a>
            <?php endif; ?>
            <span class="spacer"></span>
            <span><?= h($usuario['nombre']) ?> (<?= h($usuario['rol']) ?>)</span>
            <a href="logout.php">Salir</a>
        </nav>
    <?php endif; ?>
    <main class="container">