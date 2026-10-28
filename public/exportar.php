<?php
declare(strict_types=1);

require __DIR__ . '/../app/auth.php';
require __DIR__ . '/../app/exportar.php';

$usuario = requerir_login();
completar_vencidas();

// Mismo alcance que el listado: el admin todas, el solicitante solo las suyas.
$filtroUsuario = es_admin() ? null : $usuario['id'];

$estatus = null;
if (isset($_GET['estatus']) && array_key_exists((int) $_GET['estatus'], ESTATUS)) {
    $estatus = (int) $_GET['estatus'];
}

exportar_promociones(
    listar_promociones($estatus, $filtroUsuario),
    es_admin(),
    'promociones_' . date('Ymd_His') . '.xlsx'
);