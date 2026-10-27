<?php
declare(strict_types=1);

require __DIR__ . '/../app/db.php';

$pdo = db();

$pdo->exec('
    CREATE TABLE IF NOT EXISTS usuarios (
        id            INTEGER PRIMARY KEY AUTOINCREMENT,
        nombre        VARCHAR(50)  NOT NULL,
        usuario       VARCHAR(30)  NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        rol           VARCHAR(15)  NOT NULL CHECK (rol IN ("SOLICITANTE", "ADMIN"))
    )
');

$pdo->exec('
    CREATE TABLE IF NOT EXISTS promociones (
        id           INTEGER PRIMARY KEY AUTOINCREMENT,
        producto     VARCHAR(50)  NOT NULL,
        importe      NUMERIC(12,2) NOT NULL,
        moneda       INTEGER NOT NULL DEFAULT 0 CHECK (moneda IN (0, 1)),
        fecha_inicio DATETIME NOT NULL,
        fecha_fin    DATETIME NOT NULL,
        estatus      INTEGER NOT NULL DEFAULT 0 CHECK (estatus IN (0, 1, 2, 3)),
        comentario   VARCHAR(255),
        usuario_id   INTEGER NOT NULL,
        created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    )
');

$stmt = $pdo->prepare('
    INSERT OR IGNORE INTO usuarios (nombre, usuario, password_hash, rol)
    VALUES (:nombre, :usuario, :hash, :rol)
');

$seed = [
    ['Solicitante Demo', 'solicitante', 'solicitante123', 'SOLICITANTE'],
    ['Administrador Demo', 'admin', 'admin123', 'ADMIN'],
];

foreach ($seed as [$nombre, $usuario, $password, $rol]) {
    $stmt->execute([
        ':nombre' => $nombre,
        ':usuario' => $usuario,
        ':hash' => password_hash($password, PASSWORD_DEFAULT),
        ':rol' => $rol,
    ]);
}

echo "Base de datos lista.\n";