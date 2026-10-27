<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function h(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

function usuario_actual(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

function es_admin(): bool
{
    return (usuario_actual()['rol'] ?? null) === 'ADMIN';
}

function intentar_login(string $usuario, string $password): bool
{
    $stmt = db()->prepare('SELECT * FROM usuarios WHERE usuario = :u');
    $stmt->execute([':u' => $usuario]);
    $fila = $stmt->fetch();

    if (!$fila || !password_verify($password, $fila['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['usuario'] = [
        'id' => (int) $fila['id'],
        'nombre' => $fila['nombre'],
        'usuario' => $fila['usuario'],
        'rol' => $fila['rol'],
    ];

    return true;
}

function cerrar_sesion(): void
{
    $_SESSION = [];
    session_destroy();
}

function requerir_login(): array
{
    $usuario = usuario_actual();

    if ($usuario === null) {
        header('Location: login.php');
        exit;
    }

    return $usuario;
}

function requerir_rol(string $rol): array
{
    $usuario = requerir_login();

    if ($usuario['rol'] !== $rol) {
        http_response_code(403);
        exit('Acceso denegado.');
    }

    return $usuario;
}