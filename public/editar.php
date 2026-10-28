<?php
declare(strict_types=1);

require __DIR__ . '/../app/auth.php';
require __DIR__ . '/../app/promociones.php';

$usuario = requerir_rol('SOLICITANTE');

$id = (int) ($_GET['id'] ?? 0);
$promo = obtener_promocion($id);

if (!$promo || (int) $promo['usuario_id'] !== $usuario['id']) {
    http_response_code(404);
    exit('Promoción no encontrada.');
}

if ((int) $promo['estatus'] !== 0) {
    http_response_code(403);
    exit('Solo se pueden editar promociones pendientes.');
}

$errores = [];
$valores = [
    'producto' => $promo['producto'],
    'importe' => (string) $promo['importe'],
    'moneda' => (string) $promo['moneda'],
    'fecha_inicio' => fecha_para_input($promo['fecha_inicio']),
    'fecha_fin' => fecha_para_input($promo['fecha_fin']),
    'comentario' => $promo['comentario'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valores = array_merge($valores, array_intersect_key($_POST, $valores));
    [$errores, $datos] = validar_promocion($_POST);

    if (!$errores) {
        if (actualizar_promocion($id, $usuario['id'], $datos)) {
            header('Location: index.php?estatus=0');
            exit;
        }
        $errores['general'] = 'La promoción ya no está pendiente y no se pudo modificar.';
    }
}

$titulo = 'Editar promoción';
require __DIR__ . '/../app/header.php';
?>
<div class="card">
    <h2>Editar promoción #<?= $id ?></h2>

    <?php if ($errores): ?>
        <div class="error"><?= h($errores['general'] ?? 'Corrige los campos marcados antes de continuar.') ?></div>
    <?php endif; ?>

    <?php $textoBoton = 'Guardar cambios'; ?>
    <?php require __DIR__ . '/../app/form_promocion.php'; ?>
</div>
<?php require __DIR__ . '/../app/footer.php'; ?>