<?php
declare(strict_types=1);

require __DIR__ . '/../app/auth.php';
require __DIR__ . '/../app/promociones.php';

$usuario = requerir_login();
completar_vencidas();

// El admin ve todas; el solicitante solo las suyas.
$filtroUsuario = es_admin() ? null : $usuario['id'];

$estatus = null;
if (isset($_GET['estatus']) && array_key_exists((int) $_GET['estatus'], ESTATUS)) {
    $estatus = (int) $_GET['estatus'];
}

$promociones = listar_promociones($estatus, $filtroUsuario);
$conteos = contar_por_estatus($filtroUsuario);

$titulo = 'Promociones';
require __DIR__ . '/../app/header.php';
?>
<div class="card">
    <h2><?= es_admin() ? 'Todas las promociones' : 'Mis promociones' ?></h2>

    <div class="tabs">
        <a href="index.php" class="<?= $estatus === null ? 'activa' : '' ?>">
            Todas (<?= array_sum($conteos) ?>)
        </a>
        <?php foreach (ESTATUS as $codigo => $nombre): ?>
            <a href="index.php?estatus=<?= $codigo ?>" class="<?= $estatus === $codigo ? 'activa' : '' ?>">
                <?= h($nombre) ?> (<?= $conteos[$codigo] ?? 0 ?>)
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (!$promociones): ?>
        <p>No hay promociones para mostrar.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <?php if (es_admin()): ?>
                        <th>Solicitante</th><?php endif; ?>
                    <th>Producto</th>
                    <th>Importe</th>
                    <th>Vigencia</th>
                    <th>Estatus</th>
                    <th>Comentario</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($promociones as $p): ?>
                    <tr>
                        <td><?= (int) $p['id'] ?></td>
                        <?php if (es_admin()): ?>
                            <td><?= h($p['solicitante']) ?></td><?php endif; ?>
                        <td><?= h($p['producto']) ?></td>
                        <td><?= h(formatear_importe($p['importe'], (int) $p['moneda'])) ?></td>
                        <td>
                            <?= h(formatear_fecha($p['fecha_inicio'])) ?><br>
                            <?= h(formatear_fecha($p['fecha_fin'])) ?>
                        </td>
                        <td><span class="badge estatus-<?= (int) $p['estatus'] ?>"><?= h(ESTATUS[(int) $p['estatus']]) ?></span>
                        </td>
                        <td>
                            <?php if ((int) $p['estatus'] === 3): ?>
                                <strong>Motivo de rechazo:</strong>
                            <?php endif; ?>
                            <?= h($p['comentario']) ?>
                        </td>
                        <td>
                            <?php if ((int) $p['estatus'] === 0): ?>
                                <?php if (es_admin()): ?>
                                    <a href="revisar.php?id=<?= (int) $p['id'] ?>">Revisar</a>
                                <?php else: ?>
                                    <a href="editar.php?id=<?= (int) $p['id'] ?>">Editar</a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../app/footer.php'; ?>