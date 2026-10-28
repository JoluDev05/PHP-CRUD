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

    <?php $textoBoton = 'Registrar solicitud'; ?>
    <?php require __DIR__ . '/../app/form_promocion.php'; ?>
</div>
<?php require __DIR__ . '/../app/footer.php'; ?>