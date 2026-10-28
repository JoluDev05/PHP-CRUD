<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

const ESTATUS = [
    0 => 'Pendiente',
    1 => 'Activa',
    2 => 'Completada',
    3 => 'Rechazada',
];

const MONEDAS = [
    0 => 'MXN',
    1 => 'USD',
];

/** Acepta "2026-10-26T14:30" (input datetime-local) o "2026-10-26 14:30[:00]". */
function parsear_fecha(string $valor): ?DateTimeImmutable
{
    foreach (['Y-m-d\TH:i', 'Y-m-d H:i:s', 'Y-m-d H:i'] as $formato) {
        $fecha = DateTimeImmutable::createFromFormat('!' . $formato, trim($valor));
        $errores = DateTimeImmutable::getLastErrors();

        if ($fecha !== false && ($errores === false || ($errores['warning_count'] === 0 && $errores['error_count'] === 0))) {
            return $fecha;
        }
    }

    return null;
}

/**
 * Devuelve [errores, datos]. Si $errores está vacío, $datos viene limpio y listo para guardar.
 */
function validar_promocion(array $in): array
{
    $errores = [];

    $producto = trim((string) ($in['producto'] ?? ''));
    if ($producto === '') {
        $errores['producto'] = 'El producto es obligatorio.';
    } elseif (mb_strlen($producto) > 50) {
        $errores['producto'] = 'El producto no puede pasar de 50 caracteres.';
    }

    $importeTxt = str_replace(',', '', trim((string) ($in['importe'] ?? '')));
    $importe = 0.0;
    if ($importeTxt === '') {
        $errores['importe'] = 'El importe es obligatorio.';
    } elseif (!preg_match('/^\d+(\.\d{1,2})?$/', $importeTxt)) {
        $errores['importe'] = 'El importe debe ser un monto válido con máximo 2 decimales.';
    } elseif ((float) $importeTxt <= 0) {
        $errores['importe'] = 'El importe debe ser mayor a 0.';
    } else {
        $importe = (float) $importeTxt;
    }

    $moneda = $in['moneda'] ?? '';
    if (!in_array((string) $moneda, ['0', '1'], true)) {
        $errores['moneda'] = 'Selecciona una moneda válida.';
    }

    $inicio = parsear_fecha((string) ($in['fecha_inicio'] ?? ''));
    if ($inicio === null) {
        $errores['fecha_inicio'] = 'La fecha de inicio es obligatoria y debe ser válida.';
    }

    $fin = parsear_fecha((string) ($in['fecha_fin'] ?? ''));
    if ($fin === null) {
        $errores['fecha_fin'] = 'La fecha de fin es obligatoria y debe ser válida.';
    } elseif ($inicio !== null && $fin <= $inicio) {
        $errores['fecha_fin'] = 'La fecha de fin debe ser posterior a la fecha de inicio.';
    }

    $comentario = trim((string) ($in['comentario'] ?? ''));
    if (mb_strlen($comentario) > 255) {
        $errores['comentario'] = 'El comentario no puede pasar de 255 caracteres.';
    }

    $datos = [
        'producto' => $producto,
        'importe' => $importe,
        'moneda' => (int) $moneda,
        'fecha_inicio' => $inicio?->format('Y-m-d H:i:s'),
        'fecha_fin' => $fin?->format('Y-m-d H:i:s'),
        'comentario' => $comentario === '' ? null : $comentario,
    ];

    return [$errores, $datos];
}

function crear_promocion(array $datos, int $usuarioId): int
{
    $stmt = db()->prepare('
        INSERT INTO promociones
            (producto, importe, moneda, fecha_inicio, fecha_fin, estatus, comentario, usuario_id)
        VALUES
            (:producto, :importe, :moneda, :fecha_inicio, :fecha_fin, 0, :comentario, :usuario_id)
    ');

    $stmt->execute([
        ':producto' => $datos['producto'],
        ':importe' => $datos['importe'],
        ':moneda' => $datos['moneda'],
        ':fecha_inicio' => $datos['fecha_inicio'],
        ':fecha_fin' => $datos['fecha_fin'],
        ':comentario' => $datos['comentario'],
        ':usuario_id' => $usuarioId,
    ]);

    return (int) db()->lastInsertId();
}

function listar_promociones(?int $estatus, ?int $usuarioId): array
{
    $sql = '
        SELECT p.*, u.nombre AS solicitante
        FROM promociones p
        JOIN usuarios u ON p.usuario_id = u.id
        WHERE 1=1
    ';
    $params = [];

    if ($estatus !== null) {
        $sql .= ' AND p.estatus = :estatus';
        $params[':estatus'] = $estatus;
    }

    if ($usuarioId !== null) {
        $sql .= ' AND p.usuario_id = :usuario_id';
        $params[':usuario_id'] = $usuarioId;
    }

    $sql .= ' ORDER BY p.fecha_inicio DESC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function contar_por_estatus(?int $usuarioId): array
{
    $sql = 'SELECT estatus, COUNT(*) FROM promociones';
    $params = [];

    if ($usuarioId !== null) {
        $sql .= ' WHERE usuario_id = :usuario_id';
        $params[':usuario_id'] = $usuarioId;
    }

    $stmt = db()->prepare($sql . ' GROUP BY estatus');
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

function formatear_importe(float|int|string $importe, int $moneda): string
{
    return '$' . number_format((float) $importe, 2) . ' ' . MONEDAS[$moneda];
}

function formatear_fecha(string $fecha): string
{
    return date('d/m/Y H:i', strtotime($fecha));
}

function obtener_promocion(int $id): ?array
{
    $stmt = db()->prepare('
        SELECT p.*, u.nombre AS solicitante
        FROM promociones p
        JOIN usuarios u ON u.id = p.usuario_id
        WHERE p.id = :id
    ');
    $stmt->execute([':id' => $id]);

    return $stmt->fetch() ?: null;
}

/** Solo actualiza si es del usuario y sigue Pendiente. Devuelve false si no pudo. */
function actualizar_promocion(int $id, int $usuarioId, array $datos): bool
{
    $stmt = db()->prepare('
        UPDATE promociones
        SET producto = :producto, importe = :importe, moneda = :moneda,
            fecha_inicio = :fecha_inicio, fecha_fin = :fecha_fin, comentario = :comentario
        WHERE id = :id AND usuario_id = :usuario_id AND estatus = 0
    ');

    $stmt->execute([
        ':producto' => $datos['producto'],
        ':importe' => $datos['importe'],
        ':moneda' => $datos['moneda'],
        ':fecha_inicio' => $datos['fecha_inicio'],
        ':fecha_fin' => $datos['fecha_fin'],
        ':comentario' => $datos['comentario'],
        ':id' => $id,
        ':usuario_id' => $usuarioId,
    ]);

    return $stmt->rowCount() === 1;
}

/** Aprueba (1) o rechaza (3) una promoción Pendiente. $comentario null conserva el actual. */
function resolver_promocion(int $id, int $estatus, ?string $comentario): bool
{
    $stmt = db()->prepare('
        UPDATE promociones
        SET estatus = :estatus, comentario = COALESCE(:comentario, comentario)
        WHERE id = :id AND estatus = 0
    ');
    $stmt->execute([':estatus' => $estatus, ':comentario' => $comentario, ':id' => $id]);

    return $stmt->rowCount() === 1;
}

/** Las Activas cuya fecha de fin ya pasó se marcan como Completadas. */
function completar_vencidas(): void
{
    $stmt = db()->prepare('UPDATE promociones SET estatus = 2 WHERE estatus = 1 AND fecha_fin < :ahora');
    $stmt->execute([':ahora' => date('Y-m-d H:i:s')]);
}

/** "2026-10-27 15:51:00" -> "2026-10-27T15:51" para el input datetime-local. */
function fecha_para_input(string $fecha): string
{
    return str_replace(' ', 'T', substr($fecha, 0, 16));
}